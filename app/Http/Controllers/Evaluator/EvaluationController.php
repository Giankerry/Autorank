<?php

namespace App\Http\Controllers\Evaluator;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ManagesGoogleDrive;
use App\Models\Application;
use App\Models\Extension;
use App\Models\Instruction;
use App\Models\ProfessionalDevelopment;
use App\Models\Research;
use App\Services\AHPService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class EvaluationController extends Controller
{
    use ManagesGoogleDrive;

    public function index(Request $request)
    {
        $perPage = 5;
        $status = $request->input('status', 'pending evaluation');
        $query = Application::with('user');
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->whereHas('user', function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%');
            });
        }
        if ($request->ajax()) {
            $offset = $request->input('offset', 0);
            $totalMatching = (clone $query)->count();
            $applications = $query->orderBy('created_at', 'desc')->skip($offset)->take($perPage)->get();
            $html = '';
            foreach ($applications as $application) {
                $html .= view('partials._application_table_row', ['application' => $application])->render();
            }
            return response()->json([
                'html' => $html,
                'hasMore' => ($offset + $perPage) < $totalMatching,
                'nextOffset' => $offset + $perPage,
            ]);
        }
        $totalCount = (clone $query)->count();
        $applications = $query->orderBy('created_at', 'desc')->take($perPage)->get();
        return view('evaluator.applications-dashboard', [
            'applications' => $applications,
            'perPage' => $perPage,
            'initialHasMore' => $totalCount > $perPage,
        ]);
    }

    public function showApplication(Application $application)
    {
        $application->load('user')
            ->loadCount(['instructions', 'researches', 'extensions', 'professionalDevelopments']);
        return view('evaluator.application-details', compact('application'));
    }

    public function showApplicationKra(Request $request, Application $application, string $kra_slug)
    {
        $perPage = 5;
        $kra_title = 'Unknown KRA';
        $query = null;
        switch ($kra_slug) {
            case 'instruction':
                $query = $application->instructions();
                $kra_title = 'KRA I: Instruction';
                break;
            case 'research':
                $query = $application->researches();
                $kra_title = 'KRA II: Research';
                break;
            case 'extension':
                $query = $application->extensions();
                $kra_title = 'KRA III: Extension';
                break;
            case 'professional-development':
                $query = $application->professionalDevelopments();
                $kra_title = 'KRA IV: Professional Development';
                break;
            default:
                abort(404, 'Invalid KRA specified.');
        }
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->input('search') . '%');
        }
        $scoreStatus = $request->input('filter', 'all');
        if ($scoreStatus === 'scored') {
            $query->whereNotNull('score');
        } elseif ($scoreStatus === 'unscored') {
            $query->whereNull('score');
        }
        if ($request->ajax()) {
            $offset = $request->input('offset', 0);
            $totalMatching = (clone $query)->count();
            $submissions = $query->orderBy('created_at', 'desc')->skip($offset)->take($perPage)->get();
            $html = '';
            foreach ($submissions as $submission) {
                $html .= view('partials._submission_table_row', [
                    'item' => $submission,
                    'kra_slug' => $kra_slug
                ])->render();
            }
            return response()->json([
                'html' => $html,
                'hasMore' => ($offset + $perPage) < $totalMatching,
                'nextOffset' => $offset + $perPage,
            ]);
        }
        $totalCount = (clone $query)->count();
        $submissions = $query->orderBy('created_at', 'desc')->take($perPage)->get();
        return view('evaluator.kra-evaluation-page', [
            'application' => $application,
            'kra_slug' => $kra_slug,
            'kra_title' => $kra_title,
            'submissions' => $submissions,
            'perPage' => $perPage,
            'initialHasMore' => $totalCount > $perPage,
        ]);
    }

    public function scoreSubmission(Request $request, string $kra_slug, int $submission_id)
    {
        $validator = Validator::make($request->all(), [
            'score' => 'required|numeric|min:0',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }
        $validatedData = $validator->validated();

        switch ($kra_slug) {
            case 'instruction':
                $model = Instruction::find($submission_id);
                break;
            case 'research':
                $model = Research::find($submission_id);
                break;
            case 'extension':
                $model = Extension::find($submission_id);
                break;
            case 'professional-development':
                $model = ProfessionalDevelopment::find($submission_id);
                break;
            default:
                return response()->json(['success' => false, 'message' => 'Invalid KRA specified.'], 400);
        }

        if (!$model) {
            return response()->json(['success' => false, 'message' => 'Submission record not found.'], 404);
        }

        try {
            $model->score = $validatedData['score'];
            $model->save();
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'A server error occurred while saving the score.'], 500);
        }
        return response()->json(['success' => true, 'message' => 'Score saved successfully!']);
    }

    public function getSubmissionFiles(string $kra_slug, int $submission_id)
    {
        $model = null;
        switch ($kra_slug) {
            case 'instruction':
                $model = Instruction::find($submission_id);
                break;
            case 'research':
                $model = Research::find($submission_id);
                break;
            case 'extension':
                $model = Extension::find($submission_id);
                break;
            case 'professional-development':
                $model = ProfessionalDevelopment::find($submission_id);
                break;
            default:
                return response()->json(['success' => false, 'message' => 'Invalid KRA specified.'], 400);
        }

        if (!$model) {
            return response()->json(['success' => false, 'message' => 'Submission record not found.'], 404);
        }

        $filesData = [];
        if ($model->google_drive_file_id) {
            $filesData[] = [
                'file_name' => $model->proof_filename ?? 'Download File',
                'file_url'  => route('evaluator.submission.view-file', ['kra_slug' => $kra_slug, 'submission_id' => $submission_id]),
            ];
        }

        return response()->json([
            'success' => true,
            'files'   => $filesData,
            'details' => $this->formatRecordDataForViewer($model, $kra_slug),
        ]);
    }

    public function viewFile(Request $request, string $kra_slug, int $submission_id)
    {
        $model = null;
        switch ($kra_slug) {
            case 'instruction':
                $model = Instruction::with('user')->find($submission_id);
                break;
            case 'research':
                $model = Research::with('user')->find($submission_id);
                break;
            case 'extension':
                $model = Extension::with('user')->find($submission_id);
                break;
            case 'professional-development':
                $model = ProfessionalDevelopment::with('user')->find($submission_id);
                break;
            default:
                abort(404, 'Invalid KRA specified.');
        }

        if (!$model || !$model->google_drive_file_id || !$model->user) {
            abort(404, 'File or file owner not found.');
        }

        return $this->viewFileById($model->google_drive_file_id, $request, $model->user);
    }

    /**
     * UPDATED: Formats a record's data to be identical to the instructor's view.
     */
    private function formatRecordDataForViewer($model, string $kra_slug): array
    {
        $data = [];
        switch ($kra_slug) {
            case 'instruction':
                // This logic is copied from your InstructionController's formatter
                switch ($model->criterion) {
                    case 'instructional-materials':
                        $data = ['Title' => $model->title, 'Category' => $model->category];
                        if ($model->type) {
                            $data['Type'] = $model->type;
                        }
                        $data['Role'] = $model->role;
                        $data['Publication Date'] = Carbon::parse($model->publication_date)->format('F j, Y');
                        break;
                    case 'mentorship-services':
                        $data = [
                            'Service Type' => $model->service_type,
                            'Role' => $model->role,
                            'Student / Competition' => $model->student_or_competition,
                            'Completion Date' => Carbon::parse($model->completion_date)->format('F j, Y'),
                            'Level' => $model->level,
                        ];
                        break;
                }
                break;

            case 'research':
                // This logic is copied from your ResearchController's formatter
                switch ($model->criterion) {
                    case 'research-outputs':
                        $data = [
                            'Title' => $model->title,
                            'Category' => $model->category,
                            'Role' => $model->role,
                            'Publication / Journal Name' => $model->journal_name,
                        ];
                        if ($model->indexing) {
                            $data['Indexing'] = $model->indexing;
                        }
                        if ($model->doi) {
                            $data['DOI'] = $model->doi;
                        }
                        $data['Publication Date'] = Carbon::parse($model->publication_date)->format('F j, Y');
                        break;
                    case 'inventions-creative-works':
                        $data = [
                            'Title' => $model->title,
                            'Type' => $model->type,
                            'Sub-Type' => $model->sub_type,
                            'Role' => $model->role,
                            'Status / Level' => $model->status_level,
                            'Date of Issue / Exhibition' => Carbon::parse($model->exhibition_date)->format('F j, Y'),
                        ];
                        break;
                }
                break;

            case 'extension':
                // This logic is copied from your ExtensionController's formatter
                switch ($model->criterion) {
                    case 'service-community':
                        $data = [
                            'Title of Service / Project' => $model->title,
                            'Category' => $model->category,
                            'Role' => $model->role,
                            'Start Date' => Carbon::parse($model->start_date)->format('F j, Y'),
                            'End Date' => Carbon::parse($model->end_date)->format('F j, Y'),
                        ];
                        if ($model->target_community) {
                            $data['Target Community'] = $model->target_community;
                        }
                        break;
                    case 'extension-involvement':
                        $data = [
                            'Program / Project Title' => $model->title,
                            'Role' => $model->role,
                            'Start Date' => Carbon::parse($model->start_date)->format('F j, Y'),
                            'End Date' => Carbon::parse($model->end_date)->format('F j, Y'),
                            'Funding Source' => $model->funding_source,
                        ];
                        break;
                    case 'admin-designation':
                        $data = [
                            'Designation / Position' => $model->title,
                            'Office / Unit' => $model->office_unit,
                            'Appointment Start Date' => Carbon::parse($model->start_date)->format('F j, Y'),
                            'Appointment End Date' => $model->end_date ? Carbon::parse($model->end_date)->format('F j, Y') : 'Ongoing',
                        ];
                        break;
                }
                break;

            case 'professional-development':
                // This logic is based on your ProfessionalDevelopmentController
                switch ($model->criterion) {
                    case 'prof-organizations':
                        $data = [
                            'Organization' => $model->title,
                            'Membership Type' => $model->membership_type,
                            'Membership Date' => Carbon::parse($model->membership_date)->format('F j, Y'),
                        ];
                        if ($model->officer_role) {
                            $data['Officer Role'] = $model->officer_role;
                        }
                        break;
                    case 'prof-training':
                        $data = [
                            'Title' => $model->title,
                            'Sponsoring Body' => $model->sponsoring_body,
                            'Type' => $model->type,
                            'Completion Date' => Carbon::parse($model->completion_date)->format('F j, Y'),
                        ];
                        if ($model->hours) {
                            $data['Number of Hours'] = $model->hours;
                        }
                        if ($model->level) {
                            $data['Level'] = $model->level;
                        }
                        break;
                }
                break;
        }

        // These fields are added to all records, matching the instructor's view
        $data['Score'] = $model->score !== null ? number_format($model->score, 2) : 'To be evaluated';
        $data['Date Uploaded'] = $model->created_at->format('F j, Y, g:i A');

        return $data;
    }

    public function calculateFinalScore(Application $application, AHPService $ahpService)
    {
        // TODO implement
    }

    private function updateApplicationKraScores(Application $application)
    {
        // TODO implement
    }
}
