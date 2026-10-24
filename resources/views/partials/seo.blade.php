@php
    $seoTitle = $title ?? ($siteName ?? App\Models\Setting::get('site_name', config('app.name')));
    $seoDescription = $description ?? (App\Models\Setting::get('site_tagline', '') ?: config('app.name'));
    $canonical = $canonical ?? url()->current();
@endphp

<title>{{ $seoTitle }}</title>
<meta name="description" content="{{ \Illuminate\Support\Str::limit($seoDescription, 160) }}">
<meta name="robots" content="{{ $robots ?? 'index, follow' }}">
<link rel="canonical" href="{{ $canonical }}">

<meta property="og:type" content="{{ $ogType ?? 'website' }}">
<meta property="og:site_name" content="{{ App\Models\Setting::get('site_name', config('app.name')) }}">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ \Illuminate\Support\Str::limit($seoDescription, 200) }}">
<meta property="og:url" content="{{ $canonical }}">
<meta name="twitter:card" content="summary">
