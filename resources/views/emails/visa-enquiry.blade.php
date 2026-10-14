@extends('emails.layout')
@section('body')
  <p style="margin-top:0">A new visa support enquiry was sent from the website.</p>
  <table cellpadding="6" cellspacing="0" style="width:100%;border-collapse:collapse;font-size:14px;margin:16px 0">
    <tr><td style="color:#6b7280;width:40%">Reference</td><td><strong>{{ $enquiry->reference }}</strong></td></tr>
    <tr><td style="color:#6b7280">Name</td><td>{{ $enquiry->full_name }}</td></tr>
    <tr><td style="color:#6b7280">Email</td><td>{{ $enquiry->email }}</td></tr>
    <tr><td style="color:#6b7280">Phone / WhatsApp</td><td>{{ $enquiry->phone }}</td></tr>
    <tr><td style="color:#6b7280">Destination country</td><td>{{ $enquiry->country }}</td></tr>
    @if($enquiry->passport_country)<tr><td style="color:#6b7280">Passport country</td><td>{{ $enquiry->passport_country }}</td></tr>@endif
    <tr><td style="color:#6b7280">Assistance required</td><td>{{ implode(', ', $enquiry->assistanceLabels()) }}</td></tr>
    <tr><td style="color:#6b7280">Applicants</td><td>{{ $enquiry->applicants }}</td></tr>
    @if($enquiry->travel_date)<tr><td style="color:#6b7280">Travel date</td><td>{{ $enquiry->travel_date->format('F j, Y') }}</td></tr>@endif
  </table>
  @if($enquiry->message)<p style="margin-bottom:4px"><strong>Question / message</strong></p><p style="margin-top:0">{!! nl2br(e($enquiry->message)) !!}</p>@endif
@endsection
