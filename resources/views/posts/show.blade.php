@extends('layouts.layouys')

@section('title', 'Simple Board')

@section('content')

@section('content')
@if (session('message'))
        {{ session('message') }}
@endif

{{ $post->title }}
{{ $post->content }} 

<a href="/posts/{{ $post->id }}/edit">Edit</a>
@endsection