{{-- Fields of one itinerary stop; $stop is null for the "add a stop" form. --}}
@php($images = $stop && $stop->getRawOriginal('images') ? (json_decode($stop->getRawOriginal('images'), true) ?? []) : [])
<div class="col-md-2"><label class="form-label fw-semibold mb-1">Arrival</label><input type="time" class="form-control" name="arrival_time" value="{{ $stop ? substr((string) $stop->arrival_time, 0, 5) : '' }}"></div>
<div class="col-md-2"><label class="form-label fw-semibold mb-1">Departure</label><input type="time" class="form-control" name="departure_time" value="{{ $stop ? substr((string) $stop->departure_time, 0, 5) : '' }}"></div>
<div class="col-md-2"><label class="form-label fw-semibold mb-1">Minutes there</label><input type="number" min="1" class="form-control" name="duration_minutes" value="{{ $stop?->duration_minutes }}"></div>
<div class="col-md-6"><label class="form-label fw-semibold mb-1">Title / location <span class="text-danger">*</span></label><input class="form-control" name="en[location_name]" value="{{ $stop?->location_name }}" required></div>
<div class="col-md-12"><label class="form-label fw-semibold mb-1">Description</label><textarea class="form-control" rows="2" name="en[description]">{{ $stop?->description }}</textarea></div>
<div class="col-md-4"><label class="form-label fw-semibold mb-1">Address</label><input class="form-control" name="address" value="{{ $stop?->address }}"></div>
<div class="col-md-4"><label class="form-label fw-semibold mb-1">Map URL</label><input class="form-control" type="url" name="map_url" value="{{ $stop?->map_url }}"></div>
<div class="col-md-4"><label class="form-label fw-semibold mb-1">Video URL</label><input class="form-control" type="url" name="video_url" value="{{ $stop?->video_url }}"></div>
<div class="col-md-4"><label class="form-label fw-semibold mb-1">What customers will see</label><textarea class="form-control" rows="2" name="en[sights]">{{ $stop?->sights }}</textarea></div>
<div class="col-md-4"><label class="form-label fw-semibold mb-1">Available activities</label><textarea class="form-control" rows="2" name="en[activities]">{{ $stop?->activities }}</textarea></div>
<div class="col-md-4"><label class="form-label fw-semibold mb-1">Additional notes</label><textarea class="form-control" rows="2" name="en[notes]">{{ $stop?->notes }}</textarea></div>
<div class="col-md-6"><label class="form-label fw-semibold mb-1">Photos <span class="text-muted fw-normal">— URLs, one per line; remove a line to remove it</span></label><textarea class="form-control" rows="2" name="images_text">{{ implode("\n", $images) }}</textarea><input type="file" class="form-control mt-1" name="images[]" accept="image/*,.heic,.heif" multiple></div>
@php($stopFieldsId = $stop?->id ? 'stop-'.$stop->id : 'new-'.\Illuminate\Support\Str::random(6))
<div class="col-md-6 d-flex flex-wrap align-items-end gap-3 pb-2">
  <div class="form-check form-check-inline mb-0"><input class="form-check-input" type="checkbox" id="entrance_included-{{ $stopFieldsId }}" name="entrance_included" value="1" @checked($stop?->entrance_included)><label class="form-check-label" for="entrance_included-{{ $stopFieldsId }}">Ticket included</label></div>
  <div class="form-check form-check-inline mb-0"><input class="form-check-input" type="checkbox" id="is_optional-{{ $stopFieldsId }}" name="is_optional" value="1" @checked($stop?->is_optional)><label class="form-check-label" for="is_optional-{{ $stopFieldsId }}">Optional</label></div>
  <div class="form-check form-check-inline mb-0"><input class="form-check-input" type="checkbox" id="own_expense-{{ $stopFieldsId }}" name="own_expense" value="1" @checked($stop?->own_expense)><label class="form-check-label" for="own_expense-{{ $stopFieldsId }}">On your own account</label></div>
</div>
