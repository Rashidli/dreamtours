@extends('emails.layout')

@section('audience', 'admin')
@section('tone', 'info')
@section('status', 'New visa enquiry')
@section('title', $enquiry->full_name)
@section('preheader', "Visa support for {$enquiry->country} · {$enquiry->reference}")
@section('intro')
  A new visa support enquiry was sent from the website. Reply to this email to answer {{ $enquiry->full_name }} directly.
@endsection

@section('body')
  @include('emails.partials.rows', ['title' => 'Enquiry', 'rows' => [
    'Reference' => $enquiry->reference,
    'Name' => $enquiry->full_name,
    'Email' => $enquiry->email,
    'Phone / WhatsApp' => $enquiry->phone,
    'Destination country' => $enquiry->country,
    'Passport country' => $enquiry->passport_country,
    'Assistance required' => implode(', ', $enquiry->assistanceLabels()),
    'Applicants' => $enquiry->applicants,
    'Travel date' => $enquiry->travel_date?->format('F j, Y'),
  ]])
  @include('emails.partials.rows', ['title' => 'Question / message', 'rows' => ['Message' => $enquiry->message]])
@endsection
