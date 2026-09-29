@php
    $seo = $seo ?? [];
    $siteName = $school['name'];
    $pageTitle = ! empty($seo['title']) ? $seo['title'].' — '.$siteName : $siteName.($settings['tagline'] !== '' ? ' — '.$settings['tagline'] : '');
    $description = $seo['description'] ?? '';
    $image = isset($seo['image']) ? $img($seo['image']) : $school['logo_url'];
    $canonical = url()->current().(request()->query('page') > 1 ? '?page='.(int) request()->query('page') : '');
@endphp
<title>{{ $pageTitle }}</title>
@if ($description !== '')
    <meta name="description" content="{{ $description }}">
@endif
@if (! empty($seo['noindex']))
    <meta name="robots" content="noindex, nofollow">
@endif
<link rel="canonical" href="{{ $canonical }}">
<meta name="theme-color" content="#0a1a3f">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:locale" content="id_ID">
<meta property="og:type" content="{{ $seo['type'] ?? 'website' }}">
<meta property="og:title" content="{{ $seo['title'] ?? $siteName }}">
@if ($description !== '')
    <meta property="og:description" content="{{ $description }}">
@endif
<meta property="og:url" content="{{ $canonical }}">
@if ($image)
    <meta property="og:image" content="{{ $image }}">
@endif
@if (! empty($seo['published_at']))
    <meta property="article:published_time" content="{{ $seo['published_at']->toIso8601String() }}">
@endif
<meta name="twitter:card" content="{{ $image ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $seo['title'] ?? $siteName }}">
@if ($description !== '')
    <meta name="twitter:description" content="{{ $description }}">
@endif
<script type="application/ld+json">{!! json_encode(array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'School',
    'name' => $siteName,
    'url' => url('/'),
    'logo' => $school['logo_url'],
    'address' => $school['address'] ?: null,
    'telephone' => $school['phone'] ?: null,
    'email' => $school['email'] ?: null,
    'sameAs' => array_values($socials) ?: null,
]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
