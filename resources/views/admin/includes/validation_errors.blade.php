@if ($errors->any())
    <!-- Alert Box - Form yuxarisinda -->
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <div class="d-flex align-items-start">
            <i class="ri-error-warning-line me-2" style="font-size: 20px;"></i>
            <div>
                <strong>Xəta!</strong> Zəhmət olmasa aşağıdakı xətaları düzəldin:
                <ul class="mb-0 mt-2 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
