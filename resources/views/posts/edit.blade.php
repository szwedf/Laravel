@extends('layouts.layouys')

@section('title', 'Simple Board')

@section('content')

<h1>Editing Post</h1>

 @if ($errors->any())
   <div class="alert alert-danger">
       <ul>
           @foreach ($errors->all() as $error)
              <li>{{ $error}}</li>
            @endforeach
       </ul>
   </div>
 @endif

<form method="POST" action="/posts/{{ $post->id }}">
 {{ csrf_field() }}
 <input type="hidden" name="_method" value="PUT">
 <div class="form-group">
     <lebel for="exampleInputEmaill">Title</lebel>
     <input type="text" class="form-cotrol" aria-describedby="emailHelp" name="title" value="{{ old('title') =='' ? $post->title : old('title') }}">
 </div>
 <div class="form-group">
     <lebel for="exampleInputpassword1">Content</lebel>
     <textarea class="form-control" name="content">{{ old('content') == '' ? $post->content : old('content') }}</textarea>
 </div>
 <button type="submit" class="btn btn-outline-parimary">Submit</button>
 </form>
 
 <a href="/posts/{{ $post->id }}">Show</a>
 <a href="/posts">Back</a>

 @endsection