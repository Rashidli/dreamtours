<div class="row">
    @foreach($permissions as $groupName => $groupPermissions)
        @php $groupId = 'group-'.\Illuminate\Support\Str::slug($groupName); @endphp
        <div class="col-md-6 col-xl-3">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <div class="form-check mb-0">
                        <input class="form-check-input group-checkbox" type="checkbox" id="{{ $groupId }}" data-group="{{ $groupName }}">
                        <label class="form-check-label fw-semibold" for="{{ $groupId }}">{{ $groupName }}</label>
                    </div>
                </div>
                <div class="card-body">
                    @foreach($groupPermissions as $permission)
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" id="perm-{{ $permission->id }}"
                                   name="permission[]" value="{{ $permission->name }}" data-group="{{ $groupName }}"
                                   @checked(in_array($permission->name, $checked ?? [], true))>
                            <label class="form-check-label" for="perm-{{ $permission->id }}">{{ $permission->name }}</label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
</div>
