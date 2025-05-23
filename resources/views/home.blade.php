@extends('layouts.main')

@section('content')
{{-- Posting Cepat --}}
<div class="card mb-3 shadow-sm border-0">
  <div class="card-body">
    @if (request()->has('search'))
    <div class="d-flex align-items-center gap-4">
        <div style="cursor: pointer;" onclick="window.location.href='{{ route('home') }}'">
            <i class="bi bi-arrow-left"></i>
        </div>
        <form action="{{ route("home") }}" method="get" class="input-group align-self-center" role="search">
            @csrf
            <span class="input-group-text rounded-start"><i class="bi bi-search"></i></span>
            <input class="form-control rounded-end" type="search" name="search" id="search" placeholder="Search" aria-label="Search" value="{{ request('search') }}">
        </form>
    </div>
    @else
    <form id="postForm" action="{{ route('upload') }}" method="POST" enctype="multipart/form-data">
      @csrf
      @isset($page)
      <input type="hidden" name="category" value="{{ $page }}">
      @endisset
      <div class="d-flex mb-3">
          <img src="{{ 
              $auth_assets->where('status', 'photo-profile')->first()
                  ? URL::asset('storage/assets/' . $auth_assets->where('status', 'photo-profile')->first()->asset . '.png') 
                  : URL::asset('photo-profile.png') }}" 
              class="rounded-circle me-3" 
              style="width: 40px; height: 40px; object-fit: cover;">
          <textarea name="desc" class="form-control" placeholder="Apa yang ingin Anda bagikan?" rows="2"></textarea>
      </div>
        <div class="d-flex justify-content-between align-items-center" style="margin-left: 3.5rem;">
            <div>
                <!-- Single file input for both image and video -->
                <input type="file" name="file" id="mediaFileInput" accept="image/*,video/*" style="display:none;" />
                
                <!-- Single button to trigger file selection -->
                <button type="button" id="mediaButton" class="btn btn-outline-secondary">
                    <i class="bi bi-image"></i>
                </button>
            </div>
            <button type="submit" class="btn btn-primary">Posting</button>
        </div>

        <!-- Preview Container -->
        <div id="previewContainer" class="mt-3" style="display:none; margin-left: 3.5rem;">
            <div id="mediaPreview" class="mt-2 d-flex">
                <div class="position-relative">
                    <button type="button" id="removePreview" class="btn btn-sm btn-light rounded-3 opacity-75 position-absolute end-0 m-1">
                        <i class="bi bi-x"></i>
                    </button>
                    <img id="imagePreview" class="rounded-3" style="max-width: 100%; max-height: 300px; display:none;" />
                    <video id="videoPreview" class="rounded-3" controls style="max-width: 100%; max-height: 300px; display:none;"></video>
                </div>
            </div>
        </div>
    </form>
    @endif
  </div>
</div>

{{-- Feed Postingan --}}
@if (count($creations) <= 0)
    <p class="text-muted text-center mt-5">no content available</p>
@endif
@foreach ($creations as $creation)

<div class="card mb-3 shadow-sm border-0">
    <div class="card-body">
        <div class="d-flex">
            <img src="{{ 
                $assets->where('user_id', '-', $creation->user_id)->where('status', 'photo-profile')->first()
                    ? URL::asset('storage/assets/' . $assets->where('user_id', '-', $creation->user_id)->where('status', 'photo-profile')->first()->asset . '.png') 
                    : URL::asset('photo-profile.png') }}" 
            class="rounded-circle me-3" 
            style="width: 40px; height: 40px; object-fit: cover;">
            <div class="w-100" >
                <div class="d-flex justify-content-between mb-2" style="cursor: pointer;">
                    <div class="w-100" onclick="window.location.href='{{ route('post-detail', ['category' => $page, 'id' => $creation->id]) }}'">
                        <div class="text-nowrap">
                            <a href="{{ route('profile', ['username' => urlencode($creation->users->username)]) }}" class="mb-0 fw-semibold text-decoration-none">{{ $creation->users->name }}</a>
                            @php
                                $crea = date('Y', strtotime($creation->created_at));
                                if (date('Y') == $crea) {
                                    $crea = date('d', strtotime($creation->created_at));
                                    if ( date('d') == $crea) {
                                        $crea = $creation->created_at->diffForHumans();
                                    } else {
                                        $crea = date('d M', strtotime($creation->created_at));
                                    }
                                } else {
                                    $crea = date('d M Y', strtotime($creation->created_at));
                                }
                            @endphp
                            <small class="text-muted"> • {{ $crea }} {{ $page == 'explore' ? '(' . $creation->categorys->name . ')' : '' }}</small>
                        </div>
                        @php
                            $token = csrf_token();
                            $desc = preg_replace_callback('/#(\w+)/', function ($match) use ($token) {
                                $tag = $match[1];
                                return '<a href="/?search=' . urlencode('#' . $tag) . '&_token=' . $token . '" class="text-primary">#' . $tag . '</a>';
                            }, e($creation->desc));
                        @endphp
                        <p class="card-text">{!! $desc !!}</p>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-link text-muted px-0" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">
                            @if ($creation->user_id == $user->id)
                                <li>
                                    <a class="dropdown-item text-danger" href="#" onclick="openModalDelete('{{ route('delete') }}', 'POST', {creation: {{ $creation->id }}})">
                                        Delete
                                    </a>
                                </li>
                            @endif
                            <li>
                                <a 
                                class="dropdown-item btn-save" 
                                href="#" 
                                data-user-id="{{ $user->id }}" 
                                data-creation-id="{{ $creation->id }}">
                                    Save
                                </a>
                            </li>
                            @if ($creation->user_id != $user->id)
                                <li><a class="dropdown-item" href="#" onclick="openModalReport('creation', {{ $creation->id }})">Report</a></li>
                            @endif
                        </ul>
                    </div>
                </div>
                @isset($creation->creation)
                <div class="mb-3" style="cursor: pointer;" onclick="window.location.href='{{ route('post-detail', ['category' => $page, 'id' => $creation->id])}}'">
                    <img src="{{ URL::asset('storage/creations/'.$creation->creation.'.'.$creation->type_file) }}" alt="Postingan" class="mw-100 rounded-3 border" style="max-height: 25rem;">
                </div>
                @endisset
                <div class="d-flex justify-content-between mb-2">
                    <div>
                        <button 
                            class="btn btn-link btn-like p-0 text-decoration-none text-danger me-2"
                            data-user-id="{{ $user->id }}" 
                            data-creation-id="{{ $creation->id }}" 
                            data-creation-user-id="{{ $creation->users->id }}">
                            <i class="bi {{ $likes->where('user_id', $user->id)->where('creation_id', $creation->id)->first() ? 'bi-heart-fill' : 'bi-heart' }}"></i>
                            <span class="like-counts">{{ count($likes->where('creation_id', $creation->id)) }}</span>
                        </button>
                        <button class="btn btn-link p-0 text-decoration-none text-muted">
                            <i class="bi {{ $comments->where('user_id', $user->id)->where('creation_id', $creation->id)->first() ? 'bi-chat-fill' : 'bi-chat' }}"></i>
                            <span class="comment-counts">{{ count($comments->where('creation_id', $creation->id)) }}</span>
                        </button>
                    </div>
                    <button 
                        class="btn btn-link btn-save p-0 text-decoration-none text-muted"
                        data-user-id="{{ $user->id }}" 
                        data-creation-id="{{ $creation->id }}">
                        <i class="bi {{ $saves->where('user_id', $user->id)->where('creation_id', $creation->id)->first() ? 'bi-bookmark-fill' : 'bi-bookmark' }}"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endforeach

<script>
$(document).ready(function() {
    // Media button click handler
    $('#mediaButton').on('click', function() {
        $('#mediaFileInput').click();
    });

    // File input change handler
    $('#mediaFileInput').on('change', function() {
        // Reset previous preview
        $('#imagePreview, #videoPreview').hide();
        $('#previewContainer').hide();

        var file = this.files[0];
        if (file) {
            // Show preview based on file type
            if (file.type.startsWith('image/')) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#imagePreview').attr('src', e.target.result).show();
                    $('#videoPreview').hide();
                    $('#previewContainer').show();
                }
                reader.readAsDataURL(file);
            } else if (file.type.startsWith('video/')) {
                var videoURL = URL.createObjectURL(file);
                $('#videoPreview').attr('src', videoURL).show();
                $('#imagePreview').hide();
                $('#previewContainer').show();
            }
        }
    });

    // Remove preview handler
    $('#removePreview').on('click', function() {
        // Clear file input
        $('#mediaFileInput').val('');
        
        // Hide preview
        $('#previewContainer').hide();
        $('#imagePreview, #videoPreview').hide();
    });
});
</script>

@endsection