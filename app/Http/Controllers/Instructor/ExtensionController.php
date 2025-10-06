<?php

namespace App\Http\Controllers\Instructor;

use App\Exceptions\GoogleAccountDisconnectedException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ManagesGoogleDrive;
use App\Models\Application;
use App\Models\Extension;
use App\Services\DataSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class ExtensionController extends Controller
{
    use ManagesGoogleDrive;

    /**
     * Find or create a draft application for the authenticated user.
     *
     * @return \App\Models\Application
     */
    private function findOrCreateDraftApplication()
    {
        $user = Auth::user();

        // Find an existing draft application or create a new one
        return Application::firstOrCreate(
            [
                'user_id' => $user->id,
                'status' => 'draft',
            ]
        );
    }

    /**
     * Centralized method to get all dropdown options for KRA III.
     */
    private function getExtensionOptions(): array
    {
        return [
            // Options for Service to the Institution/Community
            'sc_categories' => [
                'Community Service / Outreach',
                'Institutional / University Service',
                'Expert Service'
            ],
            'sc_roles' => ['Project Lead', 'Team Member', 'Volunteer', 'Consultant', 'Organizer'],

            // Options for Extension Program/Project Involvement
            'ei_roles' => ['Program Leader', 'Project Leader', 'Team Member', 'Contributor'],
        ];
    }

    public function index(Request $request, DataSearchService $searchService)
    {
        $perPage = 5;
        $userId = Auth::id();

        if ($request->ajax()) {
            $criterion = $request->input('criterion', 'service-community');
            $searchTerm = $request->input('search');
            $offset = $request->input('offset', 0);

            $query = Extension::where('user_id', $userId)->where('criterion', $criterion)->orderBy('created_at', 'desc');

            $searchableColumns = [
                'service-community' => ['title', 'category', 'role', 'target_community'],
                'extension-involvement' => ['title', 'role', 'funding_source'],
                'admin-designation' => ['title', 'office_unit'],
            ];

            if ($searchTerm && isset($searchableColumns[$criterion])) {
                $searchService->applySearch($query, $searchTerm, $searchableColumns[$criterion]);
            }

            $totalMatching = (clone $query)->count();
            $items = $query->skip($offset)->take($perPage)->get();
            $html = '';
            $partialName = 'partials._' . str_replace('-', '_', $criterion) . '_table_row';

            if (view()->exists($partialName)) {
                foreach ($items as $item) {
                    $html .= view($partialName, ['item' => $item])->render();
                }
            }

            return response()->json([
                'html'       => $html,
                'hasMore'    => ($offset + $perPage) < $totalMatching,
                'nextOffset' => $offset + $perPage,
            ]);
        }

        $serviceCommunityData = Extension::where('user_id', $userId)->where('criterion', 'service-community')->orderBy('created_at', 'desc');

        $extensionOptions = $this->getExtensionOptions();

        return view('instructor.extension-page', [
            'serviceCommunityData'     => (clone $serviceCommunityData)->take($perPage)->get(),
            'extensionInvolvementData' => Extension::where('user_id', $userId)->where('criterion', 'extension-involvement')->orderBy('created_at', 'desc')->take($perPage)->get(),
            'adminDesignationData'     => Extension::where('user_id', $userId)->where('criterion', 'admin-designation')->orderBy('created_at', 'desc')->take($perPage)->get(),
            'perPage'                  => $perPage,
            'initialHasMore'           => $serviceCommunityData->count() > $perPage,
            'extensionOptions'         => $extensionOptions,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $criterion = $request->input('criterion');
            $validatedData = $this->validateRequest($request, $criterion, $user->id);

            // Get or create the draft application for this evaluation cycle
            $draftApplication = $this->findOrCreateDraftApplication();

            $kraFolderName = 'KRA III: Extension and Community Involvement';
            $folderNameMap = [
                'service-community'     => 'Service to the Institution or Community',
                'extension-involvement'  => 'Extension Program or Project Involvement',
                'admin-designation'      => 'Administrative Designation',
            ];
            $subFolderName = $folderNameMap[$criterion] ?? ucfirst(str_replace('-', ' ', $criterion));

            $dataToCreate = [
                'user_id' => $user->id,
                'application_id' => $draftApplication->id, // Link to the application cycle
                'criterion' => $criterion,
            ];

            // Handle 'ongoing' checkbox for admin-designation
            if ($criterion === 'admin-designation' && $request->has('ongoing')) {
                $validatedData['end_date'] = null;
            }

            foreach ($validatedData as $key => $value) {
                if (!$request->hasFile($key) && $key !== 'ongoing') {
                    $dataToCreate[$key] = $value;
                }
            }

            if ($request->hasFile('proof_file')) {
                $fileId = $this->uploadFileToGoogleDrive($request, 'proof_file', $kraFolderName, $subFolderName);
                $dataToCreate['google_drive_file_id'] = $fileId;
                $dataToCreate['filename'] = $request->file('proof_file')->getClientOriginalName();
            }

            Extension::create($dataToCreate);

            return response()->json(['success' => true, 'message' => 'Successfully uploaded!'], 201);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'The given data was invalid.', 'errors' => $e->errors()], 422);
        } catch (GoogleAccountDisconnectedException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            Log::error('Extension Upload Failed: ' . $e->getMessage());
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    private function validateRequest(Request $request, string $criterion, int $userId): array
    {
        $rules = [];
        $options = $this->getExtensionOptions();

        if ($criterion === 'service-community') {
            $rules = [
                'title'            => ['required', 'string', 'max:255', Rule::unique('extensions')->where('user_id', $userId)->where('criterion', $criterion)],
                'category'         => ['required', Rule::in($options['sc_categories'])],
                'role'             => ['required', Rule::in($options['sc_roles'])],
                'start_date'       => 'required|date|before_or_equal:today',
                'end_date'         => 'required|date|after_or_equal:start_date',
                'target_community' => [
                    'nullable',
                    'string',
                    'max:255',
                    Rule::requiredIf(fn() => $request->input('category') === 'Community Service / Outreach'),
                ],
                'proof_file'       => 'required|file|mimes:pdf,doc,docx,jpg,png|max:5120',
            ];
        } elseif ($criterion === 'extension-involvement') {
            $rules = [
                'title'          => ['required', 'string', 'max:255', Rule::unique('extensions')->where('user_id', $userId)->where('criterion', $criterion)],
                'role'           => ['required', Rule::in($options['ei_roles'])],
                'start_date'     => 'required|date|before_or_equal:today',
                'end_date'       => 'required|date|after_or_equal:start_date',
                'funding_source' => 'required|string|max:255',
                'proof_file'     => 'required|file|mimes:pdf,doc,docx,jpg,png|max:5120',
            ];
        } elseif ($criterion === 'admin-designation') {
            $rules = [
                'title'        => ['required', 'string', 'max:255'],
                'office_unit'  => 'required|string|max:255',
                'start_date'   => 'required|date|before_or_equal:today',
                'end_date'     => [
                    'nullable',
                    'date',
                    'after_or_equal:start_date',
                    Rule::requiredIf(fn() => !$request->has('ongoing')),
                ],
                'ongoing'      => 'nullable|boolean',
                'proof_file'   => 'required|file|mimes:pdf,doc,docx,jpg,png|max:5120',
            ];
        }

        return $request->validate($rules);
    }

    public function destroy(Extension $extension): JsonResponse
    {
        if ($extension->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        try {
            if ($extension->google_drive_file_id) {
                $this->deleteFileFromGoogleDrive($extension->google_drive_file_id, $extension->user);
            }
            $extension->delete();
            return response()->json(['message' => 'Item deleted successfully.']);
        } catch (GoogleAccountDisconnectedException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            Log::error('Extension Deletion Failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete the item. Please try again later.'], 500);
        }
    }

    public function getFileInfo($id)
    {
        $extension = Extension::findOrFail($id);
        if ($extension->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $filesData = [];
        if ($extension->google_drive_file_id) {
            $filesData[] = [
                'file_name' => $extension->filename ?? 'Download File',
                'file_url'  => route('instructor.extension.view-file', ['id' => $id]),
            ];
        }
        return response()->json([
            'success' => true,
            'files'   => $filesData,
            'details' => $this->formatRecordDataForViewer($extension),
        ]);
    }

    public function viewFile($id, Request $request)
    {
        $extension = Extension::with('user')->findOrFail($id);

        if ($extension->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        return $this->viewFileById($extension->google_drive_file_id, $request, $extension->user);
    }

    /**
     * Formats an Extension record's data into a human-readable array for the file viewer.
     *
     * @param \App\Models\Extension $extension
     * @return array
     */
    private function formatRecordDataForViewer(Extension $extension): array
    {
        $data = [];
        switch ($extension->criterion) {
            case 'service-community':
                $data = [
                    'Title of Service / Project' => $extension->title,
                    'Category' => $extension->category,
                    'Role' => $extension->role,
                    'Start Date' => Carbon::parse($extension->start_date)->format('F j, Y'),
                    'End Date' => Carbon::parse($extension->end_date)->format('F j, Y'),
                    'Score' => $extension->score !== null ? number_format($extension->score, 2) : 'To be evaluated',
                ];
                if ($extension->target_community) {
                    $data['Target Community'] = $extension->target_community;
                }
                break;

            case 'extension-involvement':
                $data = [
                    'Program / Project Title' => $extension->title,
                    'Role' => $extension->role,
                    'Start Date' => Carbon::parse($extension->start_date)->format('F j, Y'),
                    'End Date' => Carbon::parse($extension->end_date)->format('F j, Y'),
                    'Funding Source' => $extension->funding_source,
                    'Score' => $extension->score !== null ? number_format($extension->score, 2) : 'To be evaluated',
                ];
                break;

            case 'admin-designation':
                $data = [
                    'Designation / Position' => $extension->title,
                    'Office / Unit' => $extension->office_unit,
                    'Appointment Start Date' => Carbon::parse($extension->start_date)->format('F j, Y'),
                    'Appointment End Date' => $extension->end_date ? Carbon::parse($extension->end_date)->format('F j, Y') : 'Ongoing',
                    'Score' => $extension->score !== null ? number_format($extension->score, 2) : 'To be evaluated',
                ];
                break;
        }

        $data['Date Uploaded'] = $extension->created_at->format('F j, Y, g:i A');
        return $data;
    }
}
