<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ManagesGoogleDrive;
use App\Models\Application;
use App\Models\Research;
use App\Services\DataSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ResearchController extends Controller
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
     * Centralized method to get all dropdown options and dependency maps for KRA II.
     */
    private function getResearchOptions(): array
    {
        return [
            // Options for Research Outputs
            'ro_categories' => [
                'Thesis / Dissertation',
                'Journal Article',
                'Book / Monograph',
                'Book Chapter',
                'Conference Paper / Proceedings'
            ],
            'ro_indexing' => [
                'Scopus-Indexed',
                'Web of Science (WoS) / Clarivate Analytics',
                'CHED-Accredited / Recognized Journal',
                'Peer-Reviewed (non-indexed)'
            ],
            'ro_roles' => ['Sole Author', 'Lead Author', 'Co-author', 'Contributor'],

            // Options for Inventions, Innovation, and Creative Works
            'icw_types' => [
                'Invention / Utility Model / Industrial Design',
                'Creative Work',
                'Software / Technology Innovation'
            ],
            // Dependency map for Type -> Sub-Type
            'icw_sub_types' => [
                'Invention / Utility Model / Industrial Design' => ['Patent', 'Utility Model', 'Industrial Design'],
                'Creative Work' => ['Visual Arts', 'Literary Work', 'Musical Work', 'Performance'],
                'Software / Technology Innovation' => ['Copyrighted Software', 'Commercialized Technology', 'Patented Software / Technology']
            ],
            // Dependency map for Type -> Status/Level
            'icw_status_levels' => [
                'Invention / Utility Model / Industrial Design' => ['Filed / Pending', 'Granted / Issued'],
                'Creative Work' => ['Institutional', 'Regional', 'National', 'International'],
                'Software / Technology Innovation' => ['Development Stage', 'Prototype', 'Deployed / Commercialized']
            ],
            'icw_roles' => ['Inventor', 'Creator', 'Designer', 'Developer', 'Contributor'],
        ];
    }

    public function index(Request $request, DataSearchService $searchService)
    {
        $perPage = 5;
        $userId = Auth::id();

        if ($request->ajax()) {
            $criterion = $request->input('criterion', 'research-outputs');
            $searchTerm = $request->input('search');
            $offset = $request->input('offset', 0);

            $query = Research::where('user_id', $userId)->where('criterion', $criterion)->orderBy('created_at', 'desc');

            $searchableColumns = [
                'research-outputs' => ['title', 'journal_name', 'category', 'indexing'],
                'inventions-creative-works' => ['title', 'type', 'sub_type', 'status_level'],
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

        $researchOutputsData = Research::where('user_id', $userId)->where('criterion', 'research-outputs')->orderBy('created_at', 'desc');
        $inventionsCreativeWorksData = Research::where('user_id', $userId)->where('criterion', 'inventions-creative-works')->orderBy('created_at', 'desc');
        $researchOptions = $this->getResearchOptions();

        // JSON-encode dependency maps for the frontend JavaScript
        $icwSubTypesJson = json_encode($researchOptions['icw_sub_types'] ?? []);
        $icwStatusLevelsJson = json_encode($researchOptions['icw_status_levels'] ?? []);

        return view('instructor.research-page', [
            'researchOutputsData'       => (clone $researchOutputsData)->take($perPage)->get(),
            'inventionsCreativeWorksData' => (clone $inventionsCreativeWorksData)->take($perPage)->get(),
            'perPage'                   => $perPage,
            'initialHasMoreResearch'    => $researchOutputsData->count() > $perPage,
            'researchOptions'           => $researchOptions,
            'icwSubTypesJson'           => $icwSubTypesJson,
            'icwStatusLevelsJson'       => $icwStatusLevelsJson,
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

            $kraFolderName = 'KRA II: Research, Innovation, and Creative Work';
            $folderNameMap = [
                'research-outputs' => 'Research Outputs Published',
                'inventions-creative-works' => 'Inventions, Innovation, & Creative Works',
            ];
            $subFolderName = $folderNameMap[$criterion] ?? ucfirst(str_replace('-', ' ', $criterion));

            $dataToCreate = [
                'user_id' => $user->id,
                'application_id' => $draftApplication->id, // Link to the application cycle
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
                $dataToCreate['filename'] = $request->file('proof_file')->getClientOriginalName();
            }

            Research::create($dataToCreate);

            return response()->json(['success' => true, 'message' => 'Successfully uploaded!'], 201);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'The given data was invalid.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Research Upload Failed: ' . $e->getMessage() . ' on line ' . $e->getLine() . ' in ' . $e->getFile());
            return response()->json(['message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    private function validateRequest(Request $request, string $criterion, int $userId): array
    {
        $rules = [];
        $options = $this->getResearchOptions();

        if ($criterion === 'research-outputs') {
            $rules = [
                'title'          => ['required', 'string', 'max:255', Rule::unique('researches')->where('user_id', $userId)->where('criterion', $criterion)],
                'role'           => ['required', Rule::in($options['ro_roles'])],
                'category'       => ['required', Rule::in($options['ro_categories'])],
                'journal_name'   => 'required|string|max:255',
                'indexing'       => [
                    'nullable',
                    'string',
                    Rule::requiredIf(fn() => in_array($request->input('category'), ['Journal Article', 'Conference Paper / Proceedings'])),
                    Rule::in($options['ro_indexing'])
                ],
                'publication_date' => 'required|date|before_or_equal:today',
                'doi'            => [
                    'nullable',
                    'string',
                    'max:255',
                ],
                'proof_file'     => 'required|file|mimes:pdf,doc,docx,jpg,png|max:5120',
            ];
        } elseif ($criterion === 'inventions-creative-works') {
            $rules = [
                'title'          => ['required', 'string', 'max:255', Rule::unique('researches')->where('user_id', $userId)->where('criterion', $criterion)],
                'role'           => ['required', Rule::in($options['icw_roles'])],
                'type'           => ['required', Rule::in($options['icw_types'])],
                'sub_type'       => [
                    'required',
                    'string',
                    // Custom validation rule to check if sub_type is valid for the selected type
                    function ($attribute, $value, $fail) use ($request, $options) {
                        $type = $request->input('type');
                        if (!isset($options['icw_sub_types'][$type]) || !in_array($value, $options['icw_sub_types'][$type])) {
                            $fail("The selected sub-type is not valid for the chosen type.");
                        }
                    },
                ],
                'status_level'   => [
                    'required',
                    'string',
                    // Custom validation rule to check if status_level is valid for the selected type
                    function ($attribute, $value, $fail) use ($request, $options) {
                        $type = $request->input('type');
                        if (!isset($options['icw_status_levels'][$type]) || !in_array($value, $options['icw_status_levels'][$type])) {
                            $fail("The selected status/level is not valid for the chosen type.");
                        }
                    },
                ],
                'exhibition_date' => 'required|date|before_or_equal:today',
                'proof_file'      => 'required|file|mimes:pdf,doc,docx,jpg,png|max:5120',
            ];
        }

        return $request->validate($rules);
    }

    public function destroy(Research $research): JsonResponse
    {
        if ($research->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        try {
            if ($research->google_drive_file_id) {
                $this->deleteFileFromGoogleDrive($research->google_drive_file_id, $research->user);
            }
            $research->delete();
            return response()->json(['message' => 'Item deleted successfully.']);
        } catch (\Exception $e) {
            Log::error('Research Deletion Failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete the item. Please try again later.'], 500);
        }
    }

    public function getFileInfo($id)
    {
        $research = Research::findOrFail($id);
        if ($research->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $filesData = [];
        if ($research->google_drive_file_id) {
            $filesData[] = [
                'file_name' => $research->filename ?? 'Download File',
                'file_url'  => route('instructor.research.view-file', ['id' => $id]),
            ];
        }
        return response()->json([
            'success' => true,
            'files'   => $filesData,
            'details' => $this->formatRecordDataForViewer($research),
        ]);
    }

    public function viewFile($id, Request $request)
    {
        $research = Research::with('user')->findOrFail($id);
        if ($research->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }
        return $this->viewFileById($research->google_drive_file_id, $request, $research->user);
    }

    private function formatRecordDataForViewer(Research $research): array
    {
        $data = [];
        switch ($research->criterion) {
            case 'research-outputs':
                $data = ['Title' => $research->title, 'Category' => $research->category, 'Role' => $research->role, 'Publication / Journal Name' => $research->journal_name,];
                if ($research->indexing) {
                    $data['Indexing'] = $research->indexing;
                }
                if ($research->doi) {
                    $data['DOI'] = $research->doi;
                }
                $data['Publication Date'] = \Carbon\Carbon::parse($research->publication_date)->format('F j, Y');
                $data['Score'] = $research->score !== null ? number_format($research->score, 2) : 'To be evaluated';
                break;
            case 'inventions-creative-works':
                $data = ['Title' => $research->title, 'Type' => $research->type, 'Sub-Type' => $research->sub_type, 'Role' => $research->role, 'Status / Level' => $research->status_level, 'Date of Issue / Exhibition' => \Carbon\Carbon::parse($research->exhibition_date)->format('F j, Y'), 'Score' => $research->score !== null ? number_format($research->score, 2) : 'To be evaluated',];
                break;
        }
        $data['Date Uploaded'] = $research->created_at->format('F j, Y, g:i A');
        return $data;
    }
}
