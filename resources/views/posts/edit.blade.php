<form method="POST" action="/posts/{{ $post->id }}">
 {{ csrf_field() }}
 <input type="hidden" name="_method" value="PUT">
 <input type="text" name="title" value="{{ $post->title }}">
 <input type="text" name="content" value="{{ $post->content }}">
 <input type="subumit">
</form>