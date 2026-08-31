@if($metadata->title)
<title>{{ $metadata->title }}</title>
@endif
@if($metadata->description)
<meta name="description" content="{{ $metadata->description }}">
@endif
@if($metadata->canonical)
<link rel="canonical" href="{{ $metadata->canonical }}">
@endif
@if($metadata->robots)
<meta name="robots" content="{{ implode(', ', $metadata->robots) }}">
@endif
@foreach($metadata->openGraph as $property => $content)
<meta property="{{ $property }}" content="{{ $content }}">
@endforeach
@foreach($metadata->twitter as $name => $content)
<meta name="{{ $name }}" content="{{ $content }}">
@endforeach
@foreach($metadata->alternates as $locale => $url)
<link rel="alternate" hreflang="{{ $locale }}" href="{{ $url }}">
@endforeach

