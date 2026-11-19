{{-- Lead contact and request fields, shared by "New lead" and the edit dialog. --}}
<div class="ld-fieldset">
  <div class="ld-fieldset-title">Who is it?</div>
  <div class="row">
    <div class="col-md-6 mb-3"><label class="form-label fw-semibold tf-req" for="lf-first">First name</label><input class="form-control" id="lf-first" name="first_name" value="{{ old('first_name', $lead->first_name) }}" required maxlength="100" autocomplete="off"></div>
    <div class="col-md-6 mb-3"><label class="form-label fw-semibold" for="lf-last">Last name</label><input class="form-control" id="lf-last" name="last_name" value="{{ old('last_name', $lead->last_name) }}" maxlength="100" autocomplete="off"></div>
    <div class="col-md-4 mb-3"><label class="form-label fw-semibold" for="lf-phone">Phone</label><input class="form-control" id="lf-phone" name="phone" value="{{ old('phone', $lead->phone) }}" maxlength="50" inputmode="tel" placeholder="e.g. 416-555-0100"></div>
    <div class="col-md-4 mb-3"><label class="form-label fw-semibold" for="lf-whatsapp">WhatsApp</label><input class="form-control" id="lf-whatsapp" name="whatsapp" value="{{ old('whatsapp', $lead->whatsapp) }}" maxlength="50" inputmode="tel" placeholder="With country code"></div>
    <div class="col-md-4 mb-3"><label class="form-label fw-semibold" for="lf-email">Email</label><input class="form-control" id="lf-email" type="email" name="email" value="{{ old('email', $lead->email) }}" placeholder="name@example.com"></div>
  </div>
</div>
<div class="ld-fieldset">
  <div class="ld-fieldset-title">What do they want?</div>
  <div class="row">
    <div class="col-md-4 mb-3"><label class="form-label fw-semibold tf-req" for="lf-source">How did they reach us?</label><select class="form-select" id="lf-source" name="source" required>@foreach(\App\Models\Lead::SOURCES as $value => $label)<option value="{{ $value }}" @selected(old('source', $lead->source) === $value)>{{ $label }}</option>@endforeach</select></div>
    <div class="col-md-5 mb-3"><label class="form-label fw-semibold" for="lf-tour">Interested in</label><select class="form-select" id="lf-tour" name="tour_id"><option value="">No specific tour</option>@foreach($tours as $tour)<option value="{{ $tour->id }}" @selected(old('tour_id', $lead->tour_id) == $tour->id)>{{ $tour->name }}</option>@endforeach</select></div>
    <div class="col-md-3 mb-3"><label class="form-label fw-semibold" for="lf-guests">Guests</label><input class="form-control" id="lf-guests" type="number" min="1" max="99" name="guests" value="{{ old('guests', $lead->guests) }}"></div>
    <div class="col-md-6 mb-3"><label class="form-label fw-semibold" for="lf-campaign">Campaign <span class="text-muted fw-normal">(optional)</span></label><input class="form-control" id="lf-campaign" name="utm_campaign" value="{{ old('utm_campaign', $lead->utm_campaign) }}" maxlength="150" placeholder="e.g. Halloween 2026 flyer"><div class="form-text">Shown on the board card, so you can see which ads bring leads.</div></div>
    <div class="col-12 mb-1"><label class="form-label fw-semibold" for="lf-message">Request details</label><textarea class="form-control" id="lf-message" name="message" rows="3" maxlength="5000" placeholder="Dates, number of people, questions they asked…">{{ old('message', $lead->message) }}</textarea></div>
  </div>
</div>
