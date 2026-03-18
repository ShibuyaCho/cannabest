@extends('frontend.app')

@section('content')
<!-- Our Story Area -->
<section class="story-area" id="Welcome">
    <div class="container">
        <div class="row">
            <!-- Our Story Left -->
            <div class="col-md-6 story-left">
                <img src="{{ asset('assets/frontend/img/about-01.jpg') }}" alt="">
            </div>
            <!-- Our Story Right -->
            <div class="col-md-6 story-right">
                <div class="story-our-text">
                    <h2>{!! homepage_by_key('story_title') !!}</h2>
                    <div class="hr-outtr-line">
                        <hr><i class="fa fa-heart" aria-hidden="true"></i><hr>
                    </div>
                    <p>{!! homepage_by_key('story_desc') !!}</p>
                    <a href="#">About Us</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Parallax-01 Area -->
<section class="parallax-action">
    <div class="balck-solid-paralax"></div>
    <div class="container">
        <div class="row">
            <div class="parallax-content-sec">
                {!! homepage_by_key('img_title1') !!}
            </div>
        </div>
    </div>
</section>

<!-- Our Menu Area -->
<section class="menu-area" id="Menu">
    <div class="container">
        <div class="row">
            <div class="menu-content-mid">
                <h2>{!! homepage_by_key('menu_title') !!}</h2>
                <div class="hr-outtr-line">
                    <hr><i class="fa fa-heart" aria-hidden="true"></i><hr>
                </div>
                <p>{!! homepage_by_key('menu_desc') !!}</p>
                <div class="menu-items-box clearfix">
                    @php
                        $cats = homepage_by_key('category');
                        $cat_array = [];
                        if (!empty($cats)) {
                            foreach (explode(",", $cats) as $c) { 
                                $cat_array[] = getCategory($c);
                            }
                        }
                    @endphp
                    @if(!empty($cats))
                        @foreach($cat_array as $cat)
                            @if(!empty($cat->id))
                                <a href="{{ url('our-menu') }}" class="menu-items col-md-3">
                                    <div class="overlay-outr">
                                        <figure class="img-hme">
                                            <img src="{{ asset('uploads/category/' . $cat->id . '.jpg') }}" alt="">
                                        </figure>
                                        <span class="overlay-sec"></span>
                                    </div>
                                    <div class="text-outr">
                                        <strong>{{ $cat->name }}</strong>
                                    </div>
                                </a>
                            @endif
                        @endforeach 
                    @endif
                </div>
                <a href="{{ url('our-menu') }}">View The menu</a>
            </div>
        </div>
    </div>
</section>

<!-- Additional sections (Parallax-02, etc.) -->
<section class="parallax-action-two">
    <div class="balck-solid-paralax"></div>
    <div class="container">
        <div class="row">
            <div class="parallax-content-sec">
                {!! homepage_by_key('img_title2') !!}
            </div>
        </div>
    </div>
</section>

@endsection
