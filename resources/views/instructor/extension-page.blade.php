@extends('layouts.view-all-layout')

@section('title', 'KRA III: Extension and Community Involvement | Autorank')

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
    <h1>KRA III: Extension and Community Involvement</h1>
    <div class="criterion-selector">
        <select id="criterion-select">
            <option value="service-community" selected>Service to the Institution / Community</option>
            <option value="extension-involvement">Extension Program / Project Involvement</option>
            <option value="admin-designation">Administrative Designation</option>
        </select>
    </div>
</div>

{{-- Criterion A: Service to the Institution/Community Table --}}
<div class="performance-metric-container" id="service-community-table">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Title of Service / Project</th>
                <th>Category</th>
                <th>Role</th>
                <th>Score</th>
                <th>
                    <div class="search-bar-container">
                        <form action="{{ route('instructor.extension-page') }}" method="GET">
                            <input type="hidden" name="criterion" value="service-community">
                            <input type="text" name="search" placeholder="Search...">
                            <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                        </form>
                    </div>
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse($serviceCommunityData as $item)
            @include('partials._service_community_table_row', ['item' => $item])
            @empty
            <tr id="no-results-row">
                <td colspan="6" style="text-align: center;">No items found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Criterion B: Extension Program/Project Involvement Table --}}
<div class="performance-metric-container" id="extension-involvement-table" style="display: none;">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Program / Project Title</th>
                <th>Role</th>
                <th>Score</th>
                <th>
                    <div class="search-bar-container">
                        <form action="{{ route('instructor.extension-page') }}" method="GET">
                            <input type="hidden" name="criterion" value="extension-involvement">
                            <input type="text" name="search" placeholder="Search...">
                            <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                        </form>
                    </div>
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse($extensionInvolvementData as $item)
            @include('partials._extension_involvement_table_row', ['item' => $item])
            @empty
            <tr id="no-results-row">
                <td colspan="5" style="text-align: center;">No items found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Criterion C: Administrative Designation Table --}}
<div class="performance-metric-container" id="admin-designation-table" style="display: none;">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Designation / Position</th>
                <th>Office / Unit</th>
                <th>Score</th>
                <th>
                    <div class="search-bar-container">
                        <form action="{{ route('instructor.extension-page') }}" method="GET">
                            <input type="hidden" name="criterion" value="admin-designation">
                            <input type="text" name="search" placeholder="Search...">
                            <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                        </form>
                    </div>
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse($adminDesignationData as $item)
            @include('partials._admin_designation_table_row', ['item' => $item])
            @empty
            <tr id="no-results-row">
                <td colspan="5" style="text-align: center;">No items found.</td>
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
{{-- ================= KRA III: UPLOAD MODALS ========================== --}}
{{-- =================================================================== --}}

{{-- Criterion A: Service to the Institution/Community Upload Modal --}}
<div class="role-modal-container" id="service-community-modal" style="display: none;">
    <div class="role-modal">
        <div class="role-modal-navigation"><i class="fa-solid fa-xmark close-modal-btn"></i></div>
        <div class="initial-step">
            <form class="kra-upload-form" action="{{ route('instructor.extension.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="criterion" value="service-community">
                <div class="role-modal-content">
                    <div class="role-modal-content-header"><h1>Upload Service to Institution / Community</h1><p>Fill out the details below.</p></div>
                    <div class="role-modal-content-body">
                        <div class="form-group"><label class="form-group-title" data-label="Title of Service/Project">Title of Service / Project *</label><input type="text" name="title" required></div>
                        <div class="form-group"><label class="form-group-title" data-label="Category">Category *</label><select class="select-input" name="category" required>
                            <option value="" disabled selected>Click here to select</option>
                            @foreach($extensionOptions['sc_categories'] as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select></div>
                        <div class="form-group"><label class="form-group-title" data-label="Role">Role *</label><select class="select-input" name="role" required>
                            <option value="" disabled selected>Click here to select</option>
                            @foreach($extensionOptions['sc_roles'] as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select></div>
                        <div class="form-group"><label class="form-group-title" data-label="Start Date">Start Date *</label><input type="date" style="color-scheme: dark;" name="start_date" required></div>
                        <div class="form-group"><label class="form-group-title" data-label="End Date">End Date *</label><input type="date" style="color-scheme: dark;" name="end_date" required></div>
                        <div class="form-group" id="target-community-group" style="display: none;"><label class="form-group-title" data-label="Target Community/Beneficiaries">Target Community / Beneficiaries *</label><input type="text" name="target_community"></div>
                        <div class="form-group"><label class="form-group-title file-upload" data-label="Proof of Service">Proof of Service * <h6>&nbsp;&nbsp;&nbsp;5MB max</h6></label><input type="file" name="proof_file" required></div>
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

{{-- Criterion B: Extension Program/Project Involvement Upload Modal --}}
<div class="role-modal-container" id="extension-involvement-modal" style="display: none;">
    <div class="role-modal">
        <div class="role-modal-navigation"><i class="fa-solid fa-xmark close-modal-btn"></i></div>
        <div class="initial-step">
            <form class="kra-upload-form" action="{{ route('instructor.extension.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="criterion" value="extension-involvement">
                <div class="role-modal-content">
                    <div class="role-modal-content-header"><h1>Upload Extension Program / Project Involvement</h1><p>Fill out the details below.</p></div>
                    <div class="role-modal-content-body">
                        <div class="form-group"><label class="form-group-title" data-label="Program/Project Title">Program / Project Title *</label><input type="text" name="title" required></div>
                        <div class="form-group"><label class="form-group-title" data-label="Role">Role *</label><select class="select-input" name="role" required>
                            <option value="" disabled selected>Click here to select</option>
                            @foreach($extensionOptions['ei_roles'] as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select></div>
                        <div class="form-group"><label class="form-group-title" data-label="Start Date">Start Date *</label><input type="date" style="color-scheme: dark;" name="start_date" required></div>
                        <div class="form-group"><label class="form-group-title" data-label="End Date">End Date *</label><input type="date" style="color-scheme: dark;" name="end_date" required></div>
                        <div class="form-group"><label class="form-group-title" data-label="Funding Source">Funding Source *</label><input type="text" name="funding_source" required></div>
                        <div class="form-group"><label class="form-group-title file-upload" data-label="Proof of Involvement">Proof of Involvement * <h6>&nbsp;&nbsp;&nbsp;5MB max</h6></label><input type="file" name="proof_file" required></div>
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

{{-- Criterion C: Administrative Designation Upload Modal --}}
<div class="role-modal-container" id="admin-designation-modal" style="display: none;">
    <div class="role-modal">
        <div class="role-modal-navigation"><i class="fa-solid fa-xmark close-modal-btn"></i></div>
        <div class="initial-step">
            <form class="kra-upload-form" action="{{ route('instructor.extension.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="criterion" value="admin-designation">
                <div class="role-modal-content">
                    <div class="role-modal-content-header"><h1>Upload Administrative Designation</h1><p>Fill out the details below.</p></div>
                    <div class="role-modal-content-body">
                        <div class="form-group"><label class="form-group-title" data-label="Designation/Position">Designation / Position *</label><input type="text" name="title" required></div>
                        <div class="form-group"><label class="form-group-title" data-label="Office/Unit">Office / Unit *</label><input type="text" name="office_unit" required></div>
                        <div class="form-group"><label class="form-group-title" data-label="Appointment Start Date">Appointment Start Date *</label><input type="date" style="color-scheme: dark;" name="start_date" required></div>
                        <div class="form-group" id="appointment-end-date-group"><label class="form-group-title" data-label="Appointment End Date">Appointment End Date *</label><input type="date" style="color-scheme: dark;" name="end_date" required></div>
                        <div class="form-group-checkbox"><input type="checkbox" id="ongoing-checkbox" name="ongoing" value="1"><label for="ongoing-checkbox">This designation is ongoing (Present)</label></div>
                        <div class="form-group"><label class="form-group-title file-upload" data-label="Proof of Designation">Proof of Designation * <h6>&nbsp;&nbsp;&nbsp;5MB max</h6></label><input type="file" name="proof_file" required></div>
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
<script src="{{ asset('js/kra-scripts.js') }}"></script>
@endpush