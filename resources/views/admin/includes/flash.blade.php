{{-- Success message after a save (controllers redirect back ->with('message', ...)). --}}
@if(session('message'))
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="ri-check-line me-1"></i>{{ session('message') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
@endif
