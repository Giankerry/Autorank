@extends('layouts.view-all-layout')

@section('title', 'KRA II: Research, Innovation, and Creative Work | Autorank')

@section('content')

@if(session('success'))
<div class="server-alert-success">
    {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="server-alert-danger">
    {{ session('error') }}
</div>
@endif

<div class="header">
    <h1>KRA II: Research, Innovation, and Creative Work</h1>
    <div class="criterion-selector">
        <select id="criterion-select">
            <option value="research-outputs" selected>Research Outputs Published</option>
            <option value="inventions-creative-works">Inventions, Innovation, & Creative Works</option>
        </select>
    </div>
</div>

{{-- Criterion A: Research Outputs Published Table --}}
<div class="performance-metric-container" id="research-outputs-table">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Category</th>
                <th>Publication / Journal Name</th>
                <th>Role</th>
                <th>Score</th>
                <th>
                    <div class="search-bar-container">
                        <form action="{{ route('instructor.research-page') }}" method="GET">
                            <input type="hidden" name="criterion" value="research-outputs">
                            <input type="text" name="search" placeholder="Search...">
                            <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                        </form>
                    </div>
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse($researchOutputsData as $item)
            @include('partials._research_outputs_table_row', ['item' => $item])
            @empty
            <tr id="no-results-row">
                <td colspan="7" style="text-align: center;">No items found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Criterion B: Inventions, Innovation, & Creative Works Table --}}
<div class="performance-metric-container" id="inventions-creative-works-table" style="display: none;">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Type</th>
                <th>Role</th>
                <th>Score</th>
                <th>
                    <div class="search-bar-container">
                        <form action="{{ route('instructor.research-page') }}" method="GET">
                            <input type="hidden" name="criterion" value="inventions-creative-works">
                            <input type="text" name="search" placeholder="Search...">
                            <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                        </form>
                    </div>
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse($inventionsCreativeWorksData as $item)
            @include('partials._inventions_creative_works_table_row', ['item' => $item])
            @empty
            <tr id="no-results-row">
                <td colspan="6" style="text-align: center;">No items found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>


<div class="load-more-container">
    <button onclick="window.history.back()">Back</button>
    <button id="upload-kra-button" class="upload-new-button">Upload New</button>
    <button id="load-more-kra-btn" data-current-offset="{{ $perPage }}"
        @if (!$initialHasMoreResearch) style="display: none;" @endif>
        Load More +
    </button>
</div>

{{-- =================================================================== --}}
{{-- ================= KRA II: UPLOAD MODALS =========================== --}}
{{-- =================================================================== --}}

{{-- Criterion A: Research Outputs Upload Modal --}}
<div class="role-modal-container" id="research-outputs-modal" style="display: none;">
    <div class="role-modal">
        <div class="role-modal-navigation">
            <i class="fa-solid fa-xmark close-modal-btn" style="color: #ffffff;"></i>
        </div>
        {{-- STEP 1: Form Input --}}
        <div class="initial-step">
            <form class="kra-upload-form" action="{{ route('instructor.research-page') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="criterion" value="research-outputs">
                <div class="role-modal-content">
                    <div class="role-modal-content-header">
                        <h1>Upload Research Output</h1>
                        <p>Fill out the details below. You will be asked to confirm before the file is uploaded.</p>
                    </div>
                    <div class="role-modal-content-body">
                        <div class="form-group"><label class="form-group-title" data-label="Title">Title *</label><input type="text" name="title" required></div>
                        <div class="form-group"><label class="form-group-title" data-label="Category">Category *</label><select class="select-input" name="category" id="ro-category" required>
                                <option value="" disabled selected>Click here to select</option>
                                @foreach($researchOptions['ro_categories'] as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                                @endforeach
                            </select></div>
                        <div class="form-group"><label class="form-group-title" data-label="Publication/Journal Name">Publication / Journal Name *</label><input type="text" name="journal_name" required></div>
                        <div class="form-group" id="ro-indexing-group" style="display: none;"><label class="form-group-title" data-label="Indexing">Indexing *</label><select class="select-input" name="indexing" required>
                                <option value="" disabled selected>Click here to select</option>
                                @foreach($researchOptions['ro_indexing'] as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                                @endforeach
                            </select></div>
                        <div class="form-group"><label class="form-group-title" data-label="Publication Date">Publication Date *</label><input type="date" name="publication_date" style="color-scheme: dark;" required></div>
                        <div class="form-group" id="ro-doi-group" style="display: none;"><label class="form-group-title" data-label="DOI">DOI</label><input type="text" name="doi"></div>
                        <div class="form-group"><label class="form-group-title" data-label="Role">Role *</label><select class="select-input" name="role" required>
                                <option value="" disabled selected>Click here to select</option>
                                @foreach($researchOptions['ro_roles'] as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                                @endforeach
                            </select></div>
                        <div class="form-group"><label class="form-group-title file-upload" data-label="Proof of Publication">Proof of Publication * <h6>&nbsp;&nbsp;&nbsp;5MB max</h6></label><input type="file" name="proof_file" required></div>
                        <div class="modal-messages mt-2"></div>
                    </div>
                </div>
                <div class="role-modal-actions"><button type="button" class="proceed-btn">Proceed</button></div>
            </form>
        </div>
        {{-- STEP 2: Confirmation --}}
        <div class="confirmation-step" style="display: none;">
            <div class="role-modal-content">
                <div class="role-modal-content-header">
                    <h1>Confirm Upload</h1>
                    <p class="confirmation-message-area"></p>
                </div>
                <div class="role-modal-content-body">
                    <div class="final-status-message-area mt-2"></div>
                </div>
            </div>
            <div class="role-modal-actions"><button type="button" class="back-btn">Back</button><button type="button" class="confirm-btn">Confirm & Upload</button></div>
        </div>
    </div>
</div>

{{-- Criterion B: Inventions, Innovation, & Creative Works Upload Modal --}}
<div class="role-modal-container" id="inventions-creative-works-modal" style="display: none;">
    <div class="role-modal">
        <div class="role-modal-navigation"><i class="fa-solid fa-xmark close-modal-btn" style="color: #ffffff;"></i></div>
        {{-- STEP 1: Form Input --}}
        <div class="initial-step">
            <form class="kra-upload-form" action="{{ route('instructor.research.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="criterion" value="inventions-creative-works">
                <div class="role-modal-content">
                    <div class="role-modal-content-header">
                        <h1>Upload Invention, Innovation, or Creative Work</h1>
                        <p>Fill out the details below. You will be asked to confirm before the file is uploaded.</p>
                    </div>
                    <div class="role-modal-content-body">
                        <div class="form-group"><label class="form-group-title" data-label="Title">Title *</label><input type="text" name="title" required></div>
                        <div class="form-group"><label class="form-group-title" data-label="Type">Type *</label><select class="select-input" name="type" id="icw-type" required>
                                <option value="" disabled selected>Click here to select</option>
                                @foreach($researchOptions['icw_types'] as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                                @endforeach
                            </select></div>
                        <div class="form-group" id="icw-subtype-group" style="display: none;"><label class="form-group-title" data-label="Sub-Type">Sub-Type *</label><select class="select-input" name="sub_type" id="icw-subtype" required>
                            <option value="" disabled selected>Click here to select</option>
                        </select></div>
                        <div class="form-group" id="icw-statuslevel-group" style="display: none;"><label class="form-group-title" data-label="Status/Level">Status / Level *</label><select class="select-input" name="status_level" id="icw-statuslevel" required>
                                <option value="" disabled selected>Click here to select</option>
                            </select></div>
                        <div class="form-group"><label class="form-group-title" data-label="Date of Issue/Exhibition">Date of Issue / Exhibition *</label><input type="date" name="exhibition_date" style="color-scheme: dark;" required></div>
                        <div class="form-group"><label class="form-group-title" data-label="Role">Role *</label><select class="select-input" name="role" required>
                                <option value="" disabled selected>Click here to select</option>
                                @foreach($researchOptions['icw_roles'] as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                                @endforeach
                            </select></div>
                        <div class="form-group"><label class="form-group-title file-upload" data-label="Proof of Output">Proof of Output * <h6>&nbsp;&nbsp;&nbsp;5MB max</h6></label><input type="file" name="proof_file" required></div>
                        <div class="modal-messages mt-2"></div>
                    </div>
                </div>
                <div class="role-modal-actions"><button type="button" class="proceed-btn">Proceed</button></div>
            </form>
        </div>
        {{-- STEP 2: Confirmation --}}
        <div class="confirmation-step" style="display: none;">
            <div class="role-modal-content">
                <div class="role-modal-content-header">
                    <h1>Confirm Upload</h1>
                    <p class="confirmation-message-area"></p>
                </div>
                <div class="role-modal-content-body">
                    <div class="final-status-message-area mt-2"></div>
                </div>
            </div>
            <div class="role-modal-actions"><button type="button" class="back-btn">Back</button><button type="button" class="confirm-btn">Confirm & Upload</button></div>
        </div>
    </div>
</div>

@include('partials._action_modals')

@endsection

@push('page-scripts')
<script>
    window.researchSubTypeOptions = {!! $icwSubTypesJson !!};
    window.researchStatusLevelOptions = {!! $icwStatusLevelsJson !!};
</script>
<script src="{{ asset('js/kra-scripts.js') }}"></script>
@endpush