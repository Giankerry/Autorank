<?php

namespace App\Http\Controllers\Instructor;

use App\Exceptions\GoogleAccountDisconnectedException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ManagesGoogleDrive;
use App\Models\Instruction;
use App\Models\Application;
use App\Services\DataSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class InstructionController extends Controller
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

        // Find an existing draft application or create a new one.
        $application = Application::firstOrCreate(
            [
                'user_id' => $user->id,
                'status' => 'draft',
            ]
        );

        return $application;
    }


    private function getInstructionalOptions(): array
    {
        return [
            'im_categories' => [
                'Instructional Material',
                'Curriculum Development',
                'Syllabus Development / Enhancement',
            ],
            'im_types' => [
                'Instructional Material' => ['Textbook', 'Workbook / Lab Manual', 'Module / Courseware', 'Multimedia Teaching Material', 'Testing Material'],
                'Syllabus Development / Enhancement' => ['Syllabus'],
            ],
            'im_roles' => ['Sole Author / Developer', 'Lead Author / Developer', 'Co-author / Co-developer', 'Contributor'],

            'ms_service_types' => [
                'Thesis / Dissertation Advising',
                'Thesis / Dissertation Panel',
                'Research Capstone / Project Mentorship',
                'Competition Coaching',
            ],
            'ms_roles' => [
                'Thesis / Dissertation Advising' => ['Adviser / Main Mentor', 'Co-adviser'],
                'Thesis / Dissertation Panel' => ['Panel Chair', 'Panel Member', 'External Examiner / Critic'],
                'Research Capstone / Project Mentorship' => ['Adviser / Main Mentor'],
                'Competition Coaching' => ['Coach'],
            ],
            'ms_levels' => ['Undergraduate', 'Master\'s', 'Doctorate'],
        ];
    }

    public function index(Request $request, DataSearchService $searchService)
    {
        $perPage = 5;
        $userId = Auth::id();

        if ($request->ajax()) {
            $criterion = $request->input('criterion', 'instructional-materials');
            $searchTerm = $request->input('search');
            $offset = $request->input('offset', 0);

            $query = Instruction::where('user_id', $userId)->where('criterion', $criterion)->orderBy('created_at', 'desc');

            $searchableColumns = [
                'instructional-materials' => ['title', 'category', 'type', 'role'],
                'mentorship-services' => ['service_type', 'role', 'student_or_competition'],
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
                    $html .= view($partialName, [
                        'item' => $item,
                    ])->render();
                }
            }

            return response()->json([
                'html'       => $html,
                'hasMore'    => ($offset + $perPage) < $totalMatching,
                'nextOffset' => $offset + $perPage,
            ]);
        }

        $instructionalMaterialsData = Instruction::where('user_id', $userId)->where('criterion', 'instructional-materials')->orderBy('created_at', 'desc');
        $instructionalOptions = $this->getInstructionalOptions();

        $imTypesJson = json_encode($instructionalOptions['im_types'] ?? []);
        $msRolesJson = json_encode($instructionalOptions['ms_roles'] ?? []);

        return view('instructor.instructional-page', [
            'instructionalMaterialsData' => Instruction::where('user_id', $userId)->where('criterion', 'instructional-materials')->orderBy('created_at', 'desc')->take($perPage)->get(),
            'mentorshipServicesData' => Instruction::where('user_id', $userId)->where('criterion', 'mentorship-services')->orderBy('created_at', 'desc')->take($perPage)->get(),
            'perPage' => $perPage,
            'initialHasMore' => $instructionalMaterialsData->count() > $perPage,
            'instructionalOptions' => $instructionalOptions,
            'imTypesJson' => $imTypesJson,
            'msRolesJson' => $msRolesJson,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $criterion = $request->input('criterion');
            $validatedData = $this->validateRequest($request, $criterion, $user->id);

            $draftApplication = $this->findOrCreateDraftApplication();

            $kraFolderName = 'KRA I: Instruction';
            $folderNameMap = [
                'instructional-materials' => 'Curriculum & Instructional Materials',
                'mentorship-services' => 'Thesis, Dissertation, & Mentorship'
            ];
            $subFolderName = $folderNameMap[$criterion] ?? ucfirst(str_replace('-', ' ', $criterion));

            $dataToCreate = [
                'user_id' => $user->id,
                'application_id' => $draftApplication->id,
                'criterion' => $criterion,
            ];

            foreach ($validatedData as $key => $value) {
                if (!$request->hasFile($key)) {
                    $dataToCreate[$key] = $value;
                }
            }

            if ($request->hasFile('proof_file')) {
                $fileId = $this->uploadFileToGoogleDrive($request, 'proof_file', $kraFolderName, $subFolderName);
                $dataToCreate['google_drive_file_id'] = $fileId;
                $dataToCreate['proof_filename'] = $request->file('proof_file')->getClientOriginalName();
            }

            Instruction::create($dataToCreate);

            return response()->json(['success' => true, 'message' => 'Successfully uploaded!'], 201);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'The given data was invalid.', 'errors' => $e->errors()], 422);
        } catch (GoogleAccountDisconnectedException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            Log::error('Instruction Upload Failed: ' . $e->getMessage());
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    private function validateRequest(Request $request, string $criterion, int $userId): array
    {
        $rules = [];
        $options = $this->getInstructionalOptions();

        if ($criterion === 'instructional-materials') {
            $rules = [
                'title' => 'required|string|max:255',
                'category' => ['required', Rule::in($options['im_categories'])],
                'type' => [
                    'nullable',
                    'string',
                    // Type is required only if the category expects it
                    Rule::requiredIf(function () use ($request) {
                        return in_array($request->input('category'), [
                            'Instructional Material',
                            'Syllabus Development/Enhancement'
                        ]);
                    }),
                ],
                'role' => ['required', Rule::in($options['im_roles'])],
                'publication_date' => 'required|date|before_or_equal:today',
                'proof_file' => 'required|file|mimes:pdf,doc,docx,jpg,png|max:5120',
            ];
        } elseif ($criterion === 'mentorship-services') {
            $uniqueRule = Rule::unique('instructions')->where(function ($query) use ($userId, $request) {
                return $query->where('user_id', $userId)
                    ->where('criterion', 'mentorship-services')
                    ->where('role', $request->input('role'));
            });

            $rules = [
                'service_type' => ['required', Rule::in($options['ms_service_types'])],
                'role' => [
                    'required',
                    function ($attribute, $value, $fail) use ($request, $options) {
                        $serviceType = $request->input('service_type');
                        if (
                            isset($options['ms_roles'][$serviceType]) &&
                            !in_array($value, $options['ms_roles'][$serviceType])
                        ) {
                            $fail("The selected role is not valid for the chosen service type.");
                        }
                    },
                ],
                'student_or_competition' => ['required', 'string', $uniqueRule],
                'completion_date' => 'required|date|before_or_equal:today',
                'level' => ['required', Rule::in($options['ms_levels'])],
                'proof_file' => 'required|file|mimes:pdf,doc,docx,jpg,png|max:5120',
            ];
        }

        return $request->validate($rules);
    }


    public function destroy(Instruction $instruction): JsonResponse
    {
        if ($instruction->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        try {
            if ($instruction->google_drive_file_id) {
                $this->deleteFileFromGoogleDrive($instruction->google_drive_file_id, $instruction->user);
            }
            $instruction->delete();
            return response()->json(['message' => 'Item deleted successfully.']);
        } catch (GoogleAccountDisconnectedException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            Log::error('Instruction Deletion Failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete the item. Please try again later.'], 500);
        }
    }

    public function getFileInfo($id, Request $request)
    {
        $instruction = Instruction::findOrFail($id);

        if ($instruction->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $filesData = [];
        if ($instruction->google_drive_file_id) {
            $filesData[] = [
                'file_name' => $instruction->proof_filename ?? 'Download File',
                'file_url'  => route('instructor.instruction.view-file', ['id' => $id]),
            ];
        }

        $recordData = $this->formatRecordDataForViewer($instruction);

        return response()->json([
            'success' => true,
            'files'   => $filesData,
            'details' => $recordData,
        ]);
    }

    public function viewFile($id, Request $request)
    {
        $instruction = Instruction::with('user')->findOrFail($id);
        if ($instruction->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }
        return $this->viewFileById($instruction->google_drive_file_id, $request, $instruction->user);
    }

    /**
     * Formats an Instruction record's data into a human-readable array for the file viewer.
     *
     * @param Instruction $instruction
     * @return array
     */
    private function formatRecordDataForViewer(Instruction $instruction): array
    {
        $data = [];
        switch ($instruction->criterion) {
            case 'instructional-materials':
                $data = [
                    'Title' => $instruction->title,
                    'Category' => $instruction->category,
                ];
                if ($instruction->type) {
                    $data['Type'] = $instruction->type;
                }
                $data['Role'] = $instruction->role;
                $data['Publication Date'] = Carbon::parse($instruction->publication_date)->format('F j, Y');
                $data['Score'] = $instruction->score !== null ? number_format($instruction->score, 2) : 'To be evaluated';
                break;
            case 'mentorship-services':
                $data = [
                    'Service Type' => $instruction->service_type,
                    'Role' => $instruction->role,
                    'Student / Competition' => $instruction->student_or_competition,
                    'Completion Date' => Carbon::parse($instruction->completion_date)->format('F j, Y'),
                    'Level' => $instruction->level,
                    'Score' => $instruction->score !== null ? number_format($instruction->score, 2) : 'To be evaluated',
                ];
                break;
        }
        $data['Date Uploaded'] = $instruction->created_at->format('F j, Y, g:i A');
        return $data;
    }
}
