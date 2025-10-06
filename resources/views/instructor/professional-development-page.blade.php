@extends('layouts.view-all-layout')

@section('title', 'KRA IV: Professional Development | Autorank')

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
    <h1>KRA IV: Professional Development</h1>
    <div class="criterion-selector">
        <select id="criterion-select">
            <option value="prof-organizations" selected>Involvement in Professional Organizations</option>
            <option value="prof-training">Continuing Professional Education & Training</option>
            <option value="prof-awards">Awards and Recognitions</option>
        </select>
    </div>
</div>

{{-- Criterion A: Involvement in Professional Organizations Table --}}
<div class="performance-metric-container" id="prof-organizations-table">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Organization Name</th>
                <th>Membership Type</th>
                <th>Role (if Officer)</th>
                <th>Score</th>
                <th>
                    <div class="search-bar-container">
                        <form action="{{ route('instructor.professional-development-page') }}" method="GET">
                            <input type="hidden" name="criterion" value="prof-organizations">
                            <input type="text" name="search" placeholder="Search...">
                            <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                        </form>
                    </div>
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse($profOrganizationsData as $item)
            @include('partials._prof_organizations_table_row', ['item' => $item])
            @empty
            <tr id="no-results-row">
                <td colspan="6" style="text-align: center;">No items found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Criterion B: Continuing Professional Education & Training Table --}}
<div class="performance-metric-container" id="prof-training-table" style="display: none;">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Title of Training / Degree</th>
                <th>Type</th>
                <th>Organizer / Institution</th>
                <th>Score</th>
                <th>
                    <div class="search-bar-container">
                        <form action="{{ route('instructor.professional-development-page') }}" method="GET">
                            <input type="hidden" name="criterion" value="prof-training">
                            <input type="text" name="search" placeholder="Search...">
                            <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                        </form>
                    </div>
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse($profTrainingData as $item)
            @include('partials._prof_training_table_row', ['item' => $item])
            @empty
            <tr id="no-results-row">
                <td colspan="6" style="text-align: center;">No items found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Criterion C: Awards and Recognitions Table --}}
<div class="performance-metric-container" id="prof-awards-table" style="display: none;">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Award Title</th>
                <th>Awarding Body</th>
                <th>Level</th>
                <th>Score</th>
                <th>
                    <div class="search-bar-container">
                        <form action="{{ route('instructor.professional-development-page') }}" method="GET">
                            <input type="hidden" name="criterion" value="prof-awards">
                            <input type="text" name="search" placeholder="Search...">
                            <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                        </form>
                    </div>
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse($profAwardsData as $item)
            @include('partials._prof_awards_table_row', ['item' => $item])
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
{{-- ================= KRA IV: UPLOAD MODALS =========================== --}}
{{-- =================================================================== --}}

{{-- Criterion A: Involvement in Professional Organizations Upload Modal --}}
<div class="role-modal-container" id="prof-organizations-modal" style="display: none;">
    <div class="role-modal">
        <div class="role-modal-navigation"><i class="fa-solid fa-xmark close-modal-btn"></i></div>
        <div class="initial-step">
            <form class="kra-upload-form" action="{{ route('instructor.professional-development.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="criterion" value="prof-organizations">
                <div class="role-modal-content">
                    <div class="role-modal-content-header"><h1>Upload Involvement in Professional Organization</h1><p>Fill out the details below.</p></div>
                    <div class="role-modal-content-body">
                        <div class="form-group"><label class="form-group-title" data-label="Organization Name">Organization Name *</label><input type="text" name="title" required></div>
                        <div class="form-group"><label class="form-group-title" data-label="Membership Type">Membership Type *</label><select class="select-input" name="membership_type" required>
                            <option value="" disabled selected>Click here to select</option>
                            @foreach($professionalDevelopmentOptions['po_membership_types'] as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select></div>
                        <div class="form-group-checkbox"><input type="checkbox" id="is-officer-checkbox" name="is_officer" value="1"><label for="is-officer-checkbox">I am an Officer</label></div>
                        <div class="form-group" id="officer-role-group" style="display: none;"><label class="form-group-title" data-label="Role (if Officer)">Role (if Officer) *</label><input type="text" name="role"></div>
                        <div class="form-group"><label class="form-group-title" data-label="Start Date">Start Date *</label><input type="date" style="color-scheme: dark;" name="start_date" required></div>
                        <div class="form-group"><label class="form-group-title" data-label="End Date">End Date *</label><input type="date" style="color-scheme: dark;" name="end_date" required></div>
                        <div class="form-group"><label class="form-group-title file-upload" data-label="Proof of Membership/Appointment">Proof of Membership/Appointment * <h6>&nbsp;&nbsp;&nbsp;5MB max</h6></label><input type="file" name="proof_file" required></div>
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

{{-- Criterion B: Continuing Professional Education & Training Upload Modal --}}
<div class="role-modal-container" id="prof-training-modal" style="display: none;">
    <div class="role-modal">
        <div class="role-modal-navigation"><i class="fa-solid fa-xmark close-modal-btn"></i></div>
        <div class="initial-step">
            <form class="kra-upload-form" action="{{ route('instructor.professional-development.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="criterion" value="prof-training">
                <div class="role-modal-content">
                    <div class="role-modal-content-header"><h1>Upload Continuing Prof. Education & Training</h1><p>Fill out the details below.</p></div>
                    <div class="role-modal-content-body">
                        <div class="form-group"><label class="form-group-title" data-label="Title of Training/Degree">Title of Training / Degree *</label><input type="text" name="title" required></div>
                        <div class="form-group"><label class="form-group-title" data-label="Type">Type *</label><select class="select-input" name="type" required>
                            <option value="" disabled selected>Click here to select</option>
                            @foreach($professionalDevelopmentOptions['pt_types'] as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select></div>
                        <div class="form-group"><label class="form-group-title" data-label="Organizer/Institution">Organizer / Institution *</label><input type="text" name="organizer" required></div>
                        <div class="form-group"><label class="form-group-title" data-label="Start Date">Start Date *</label><input type="date" name="start_date" style="color-scheme: dark;" required></div>
                        <div class="form-group"><label class="form-group-title" data-label="Completion Date">Completion Date *</label><input type="date" style="color-scheme: dark;" name="end_date" required></div>
                        <div class="form-group" id="training-hours-group" style="display: none;"><label class="form-group-title" data-label="Number of Hours">Number of Hours *</label><input type="number" name="hours" style="color-scheme: dark;" min="1"></div>
                        <div class="form-group" id="training-level-group" style="display: none;"><label class="form-group-title" data-label="Level">Level *</label><input type="text" name="level"></div>
                        <div class="form-group"><label class="form-group-title file-upload" data-label="Proof of Completion">Proof of Completion * <h6>&nbsp;&nbsp;&nbsp;5MB max</h6></label><input type="file" name="proof_file" required></div>
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

{{-- Criterion C: Awards and Recognitions Upload Modal --}}
<div class="role-modal-container" id="prof-awards-modal" style="display: none;">
    <div class="role-modal">
        <div class="role-modal-navigation"><i class="fa-solid fa-xmark close-modal-btn"></i></div>
        <div class="initial-step">
            <form class="kra-upload-form" action="{{ route('instructor.professional-development.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="criterion" value="prof-awards">
                <div class="role-modal-content">
                    <div class="role-modal-content-header"><h1>Upload Award and Recognition</h1><p>Fill out the details below.</p></div>
                    <div class="role-modal-content-body">
                        <div class="form-group"><label class="form-group-title" data-label="Award Title">Award Title *</label><input type="text" name="title" required></div>
                        <div class="form-group"><label class="form-group-title" data-label="Awarding Body">Awarding Body *</label><input type="text" name="awarding_body" required></div>
                        <div class="form-group"><label class="form-group-title" data-label="Level">Level *</label><select class="select-input" name="level" required>
                            <option value="" disabled selected>Click here to select</option>
                             @foreach($professionalDevelopmentOptions['pa_levels'] as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select></div>
                        <div class="form-group"><label class="form-group-title" data-label="Date Awarded">Date Awarded *</label><input type="date" style="color-scheme: dark;" name="end_date" required></div>
                        <div class="form-group"><label class="form-group-title file-upload" data-label="Proof of Award">Proof of Award * <h6>&nbsp;&nbsp;&nbsp;5MB max</h6></label><input type="file" name="proof_file" required></div>
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