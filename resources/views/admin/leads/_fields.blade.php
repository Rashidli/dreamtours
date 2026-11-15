{{-- Lead contact and request fields, shared by "New lead" and the edit dialog. --}}
<div class="row">
  <div class="col-md-6 mb-3"><label class="form-label">First name *</label><input class="form-control" name="first_name" value="{{ old('first_name', $lead->first_name) }}" required maxlength="100"></div>
  <div class="col-md-6 mb-3"><label class="form-label">Last name</label><input class="form-control" name="last_name" value="{{ old('last_name', $lead->last_name) }}" maxlength="100"></div>
  <div class="col-md-4 mb-3"><label class="form-label">Phone</label><input class="form-control" name="phone" value="{{ old('phone', $lead->phone) }}" maxlength="50" inputmode="tel"></div>
  <div class="col-md-4 mb-3"><label class="form-label">WhatsApp</label><input class="form-control" name="whatsapp" value="{{ old('whatsapp', $lead->whatsapp) }}" maxlength="50" inputmode="tel"></div>
  <div class="col-md-4 mb-3"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="{{ old('email', $lead->email) }}"></div>
  <div class="col-md-4 mb-3"><label class="form-label">How did they reach us? *</label><select class="form-select" name="source" required>@foreach(\App\Models\Lead::SOURCES as $value => $label)<option value="{{ $value }}" @selected(old('source', $lead->source) === $value)>{{ $label }}</option>@endforeach</select></div>
  <div class="col-md-5 mb-3"><label class="form-label">Interested in</label><select class="form-select" name="tour_id"><option value="">No specific tour</option>@foreach($tours as $tour)<option value="{{ $tour->id }}" @selected(old('tour_id', $lead->tour_id) == $tour->id)>{{ $tour->name }}</option>@endforeach</select></div>
  <div class="col-md-3 mb-3"><label class="form-label">Guests</label><input class="form-control" type="number" min="1" max="99" name="guests" value="{{ old('guests', $lead->guests) }}"></div>
  <div class="col-md-6 mb-3"><label class="form-label">Campaign <small class="text-muted">(optional)</small></label><input class="form-control" name="utm_campaign" value="{{ old('utm_campaign', $lead->utm_campaign) }}" maxlength="150" placeholder="e.g. Halloween 2026 flyer"></div>
  <div class="col-12 mb-1"><label class="form-label">What do they want?</label><textarea class="form-control" name="message" rows="3" maxlength="5000">{{ old('message', $lead->message) }}</textarea></div>
</div>
