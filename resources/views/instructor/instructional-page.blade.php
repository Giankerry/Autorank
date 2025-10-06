@extends('layouts.view-all-layout')

@section('title', 'KRA I: Instruction | Autorank')

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
    <h1>KRA I: Instruction</h1>
    <div class="criterion-selector">
        <select id="criterion-select">
            <option value="instructional-materials" selected>Curriculum & Instructional Materials</option>
            <option value="mentorship-services">Thesis, Dissertation, & Mentorship</option>
        </select>
    </div>
</div>

{{-- Criterion A: Instructional Materials Table --}}
<div class="performance-metric-container" id="instructional-materials-table" style="display: none;">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Category</th>
                <th>Role</th>
                <th>Score</th>
                <th>
                    <div class="search-bar-container">
                        <form action="{{ route('instructor.instructional-page') }}" method="GET">
                            <input type="hidden" name="criterion" value="instructional-materials">
                            <input type="text" name="search" placeholder="Search...">
                            <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                        </form>
                    </div>
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse($instructionalMaterialsData as $item)
            @include('partials._instructional_materials_table_row', [
            'item' => $item,
            ])
            @empty
            <tr id="no-results-row">
                <td colspan="6" style="text-align: center;">No items found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Criterion B: Mentorship Services Table --}}
<div class="performance-metric-container" id="mentorship-services-table" style="display: none;">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Service Type</th>
                <th>Role</th>
                <th>Level</th>
                <th>Score</th>
                <th>
                    <div class="search-bar-container">
                        <form action="{{ route('instructor.instructional-page') }}" method="GET">
                            <input type="hidden" name="criterion" value="mentorship-services">
                            <input type="text" name="search" placeholder="Search...">
                            <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                        </form>
                    </div>
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse ($mentorshipServicesData as $item)
            @include('partials._mentorship_services_table_row', [
            'item' => $item,
            ])
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
        @if (!$initialHasMore) style="display: none;" @endif>
        Load More +
    </button>
</div>

{{-- =================================================================== --}}
{{-- ================= KRA I: UPLOAD MODALS ============================ --}}
{{-- =================================================================== --}}

{{-- Criterion A: Instructional Materials Upload Modal --}}
<div class="role-modal-container" id="instructional-materials-modal" style="display: none;">
    <div class="role-modal">
        <div class="role-modal-navigation">
            <i class="fa-solid fa-xmark close-modal-btn" style="color: #ffffff;"></i>
        </div>
        <div class="initial-step">
            <form class="kra-upload-form" action="{{ route('instructor.instruction.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="criterion" value="instructional-materials">
                <div class="role-modal-content">
                    <div class="role-modal-content-header">
                        <h1>Upload Instructional Material</h1>
                        <p>Fill out the details below.</p>
                    </div>
                    <div class="role-modal-content-body">
                        <div class="form-group"><label class="form-group-title" data-label="Title">Title *</label><input type="text" name="title" required></div>
                        <div class="form-group"><label class="form-group-title" data-label="Category">Category *</label>
                            <select class="select-input" name="category" id="im-category" required>
                                <option value="" disabled selected>Click here to select</option>
                                @foreach ($instructionalOptions['im_categories'] as $category)
                                <option value="{{ $category }}">{{ $category }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" id="im-type-group" style="display: none;">
                            <label class="form-group-title" data-label="Type of Material">Type of Material *</label>
                            <select class="select-input" name="type" id="im-type">
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-group-title" data-label="Role">Role *</label>
                            <select class="select-input" name="role" required>
                                <option value="" disabled selected>Click here to select</option>
                                @foreach ($instructionalOptions['im_roles'] as $role)
                                    <option value="{{ $role }}">{{ $role }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group"><label class="form-group-title" data-label="Publication/Approval Date">Publication / Approval Date *</label><input type="date" name="publication_date" style="color-scheme: dark;" required></div>
                        <div class="form-group"><label class="form-group-title file-upload" data-label="Proof of Output">Proof of Output * <h6>&nbsp;&nbsp;&nbsp;5MB max</h6></label><input type="file" name="proof_file" required></div>
                        <div class="modal-messages mt-2"></div>
                    </div>
                </div>
                <div class="role-modal-actions"><button type="button" class="proceed-btn">Proceed</button></div>
            </form>
        </div>
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

{{-- Criterion B: Mentorship Services Upload Modal --}}
<div class="role-modal-container" id="mentorship-services-modal" style="display: none;">
    <div class="role-modal">
        <div class="role-modal-navigation"><i class="fa-solid fa-xmark close-modal-btn" style="color: #ffffff;"></i></div>
        <div class="initial-step">
            <form class="kra-upload-form" action="{{ route('instructor.instruction.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="criterion" value="mentorship-services">
                <div class="role-modal-content">
                    <div class="role-modal-content-header">
                        <h1>Upload Mentorship and Service</h1>
                        <p>Fill out the details below.</p>
                    </div>
                    <div class="role-modal-content-body">
                        <div class="form-group">
                            <label class="form-group-title" data-label="Service Type">Service Type *</label>
                            <select class="select-input" name="service_type" id="ms-service-type" required>
                                <option value="" disabled selected>Click here to select</option>
                                @foreach ($instructionalOptions['ms_service_types'] as $service)
                                <option value="{{ $service }}">{{ $service }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-group-title" data-label="Role">Role *</label>
                            <select class="select-input" name="role" id="ms-role" required>
                                <option value="" disabled selected>Select a service type first</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-group-title" id="ms-student-label" data-label="Student Name(s) or Competition Title">Student Name(s) or Competition Title *</label>
                            <input type="text" name="student_or_competition" required>
                        </div>
                        <div class="form-group">
                            <label class="form-group-title" data-label="Completion or Award Date">Completion or Award Date *</label>
                            <input type="date" name="completion_date" style="color-scheme: dark;" required>
                        </div>
                        <div class="form-group">
                            <label class="form-group-title" data-label="Level">Level *</label>
                            <select class="select-input" name="level" required>
                                <option value="" disabled selected>Click here to select</option>
                                @foreach ($instructionalOptions['ms_levels'] as $level)
                                <option value="{{ $level }}">{{ $level }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-group-title file-upload" data-label="Proof of Service">Proof of Service * <h6>&nbsp;&nbsp;&nbsp;5MB max</h6></label>
                            <input type="file" name="proof_file" required>
                        </div>
                        <div class="modal-messages mt-2"></div>
                    </div>
                </div>
                <div class="role-modal-actions"><button type="button" class="proceed-btn">Proceed</button></div>
            </form>
        </div>
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

@endsection

@push('page-scripts')
<script>
    window.instructionalMaterialOptions = {!! $imTypesJson !!};
    window.mentorshipRoleOptions = {!! $msRolesJson !!};
</script>
<script src="{{ asset('js/kra-scripts.js') }}"></script>
@endpush