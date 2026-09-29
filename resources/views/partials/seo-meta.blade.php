@php
    $seoTitle = $title ?? null;
    $seoDesc = $description ?? null;
    $seoImage = isset($image) && $image ? (str_starts_with($image, 'http') ? $image : asset($image)) : null;
    $seoType = $type ?? null;

    if ($seoTitle) {
        \Artesaos\SEOTools\Facades\SEOMeta::setTitle($seoTitle, false);
        \Artesaos\SEOTools\Facades\OpenGraph::setTitle($seoTitle);
        \Artesaos\SEOTools\Facades\TwitterCard::setTitle($seoTitle);
        \Artesaos\SEOTools\Facades\JsonLd::setTitle($seoTitle);
    }
    if ($seoDesc) {
        \Artesaos\SEOTools\Facades\SEOMeta::setDescription($seoDesc);
        \Artesaos\SEOTools\Facades\OpenGraph::setDescription($seoDesc);
        \Artesaos\SEOTools\Facades\TwitterCard::setDescription($seoDesc);
        \Artesaos\SEOTools\Facades\JsonLd::setDescription($seoDesc);
    }
    if ($seoImage) {
        \Artesaos\SEOTools\Facades\OpenGraph::addImage($seoImage);
        \Artesaos\SEOTools\Facades\TwitterCard::setImage($seoImage);
        \Artesaos\SEOTools\Facades\JsonLd::addImage($seoImage);
    }
    if ($seoType) {
        \Artesaos\SEOTools\Facades\OpenGraph::addProperty('type', $seoType);
    }
@endphp

{!! SEO::generate() !!}
<meta property="twitter:title" content="{{ $seoTitle ?? 'WALHI Jawa Barat' }}">
<meta property="twitter:description" content="{{ $seoDesc ?? 'Organisasi gerakan lingkungan hidup independen terbesar di Jawa Barat.' }}">
@if($seoImage)
<meta property="twitter:image" content="{{ $seoImage }}">
@endif
<link rel="canonical" href="{{ url()->current() }}">
<meta name="google-site-verification" content="google8abf3dd50328ee1e">
