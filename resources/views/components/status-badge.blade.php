{{-- Works with ApplicationStatus and LeadStage enums. --}}
@props(['status'])
<x-badge :color="$status->badge()">{{ $status->label() }}</x-badge>
