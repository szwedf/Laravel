@extends('layouts.layouys')

@section('title', 'Simple Board')

@section('content')

@section('content')
@if (session('message'))
        {{ session('message') }}
@endif
<div class="card">
      <div class="card-body">
           <h5 class="card-title">{{ $post->title }}</h5>
           <p class="card-text">{{ $post->content }}</p>
           
         <div class="d-flex" style="height: 36.4px;">
           <button class="btn btn-outline-primary">Show</button>
            <a href="/posts/{{ $post->id }}/edit" class="btn btn-outline-pamry">Edit</a>
            <form action="/posts/{{ $post->id }}" method="POST" ousubmit="if(confrim('Delete? Are you sure?')) {return true} else {return false};">
                    <input type="hidden" name="_method" value="DELETE">
                    <input type="hidden" name="_token" value="{{ crsf_token() }}">
                    <button type="submit" class=btn btn-outline-danger">Delete</button>
            </form>
         </div>
    </div>
</div>

{{ $post->title }}
{{ $post->content }} 

<a href="/posts/{{ $post->id }}/edit">Edit</a>
<a href="/posts">Back</a>
@endsection