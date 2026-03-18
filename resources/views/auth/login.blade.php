<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>{{ setting_by_key('title') }}</title>

  <!-- Bootstrap & Animate.css -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

  <style>
    body, html {
      height: 100%;
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      overflow: hidden;
    }

    .login-bg-container {
      position: fixed;
      top: -15%; left: -15%;
      width: 130%; height: 130%;
      z-index: -1;
      overflow: hidden;
      background-color: #000;
      transition: transform 0.2s ease-out;
    }

    .login-bg {
      position: absolute;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background-color: rgba(0, 0, 0, 0.3);
      background-image: url('{{ asset("uploads/THC2.png") }}');
      background-repeat: no-repeat;
      background-position: center;
      background-size: cover;
      opacity: 0.5;
    }

    .modal-header {
      background: #333;
      color: #fff;
    }

    .modal-content {
      border-radius: 8px;
      overflow: hidden;
      background: rgba(34, 34, 34, 0.9);
      backdrop-filter: blur(5px);
    }

    .modal-footer {
      border-top: none;
    }

    .form-control:focus {
      box-shadow: none;
      border-color: #666;
    }

    .modal-dialog {
      transition: transform 0.2s ease-out;
    }

    .input-group-append .btn {
      border-color: #ccc;
    }
  </style>
</head>
<body>
@if(session('error'))
  <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="login-bg-container"><div class="login-bg"></div></div>

<!-- Login Modal -->
<div class="modal fade" id="loginModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content animate__animated" id="loginContent">
      <div class="modal-header">
        <h5 class="modal-title mx-auto">Welcome to Cannabest</h5>
      </div>
      <div class="modal-body px-5">
        <form method="POST" action="{{ route('login') }}">
          @csrf
          <div class="form-group">
            <label class="text-white">Email</label>
            <input name="email" type="email" class="form-control" placeholder="you@example.com" required autofocus>
          </div>
          <div class="form-group">
            <label class="text-white">Password</label>
            <div class="input-group">
              <input name="password" type="password" class="form-control password-field" placeholder="••••••••" required>
              <div class="input-group-append">
                <button class="btn btn-outline-secondary toggle-password" type="button">
                  <i class="fa fa-eye"></i>
                </button>
              </div>
            </div>
          </div>
          <button type="submit" class="btn btn-block btn-primary mt-4">Sign In</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Register Modal -->
<div class="modal fade" id="registerModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content animate__animated" id="registerContent">
      <div class="modal-header">
        <h5 class="modal-title mx-auto">Create Your Account</h5>
      </div>
      <div class="modal-body px-5">
        <form method="POST" action="{{ url('/register') }}">
          @csrf
          <div class="form-group">
            <label class="text-white">Name</label>
            <input name="name" type="text" class="form-control" placeholder="Full name" required>
          </div>
          <div class="form-group">
            <label class="text-white">Email</label>
            <input name="email" type="email" class="form-control" placeholder="you@example.com" required>
          </div>
          <div class="form-group">
            <label class="text-white">Password</label>
            <div class="input-group">
              <input name="password" type="password" class="form-control password-field" placeholder="••••••••" required>
              <div class="input-group-append">
                <button class="btn btn-outline-secondary toggle-password" type="button">
                  <i class="fa fa-eye"></i>
                </button>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label class="text-white">Confirm Password</label>
            <div class="input-group">
              <input name="password_confirmation" type="password" class="form-control password-field" placeholder="••••••••" required>
              <div class="input-group-append">
                <button class="btn btn-outline-secondary toggle-password" type="button">
                  <i class="fa fa-eye"></i>
                </button>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label class="text-white">Account Type</label>
            <select name="role_id" class="form-control" required>
              <option value="" disabled selected>— Select Type —</option>
              <option value="3">Budtender</option>
              <option value="7">Customer</option>
              <option value="6">Wholesale</option>
            </select>
          </div>
          <button type="submit" class="btn btn-block btn-success mt-3">Create Account</button>
        </form>
      </div>
      <div class="modal-footer justify-content-center">
        <small class="text-white">
          Already have an account?
          <a href="#" data-toggle="modal" data-target="#loginModal" data-dismiss="modal">Sign in</a>
        </small>
      </div>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(function(){
  $('#loginModal').modal({ backdrop: 'static', keyboard: false, show: true });

  $('#loginContent').addClass('animate__zoomIn');
  $('#loginModal, #registerModal').on('show.bs.modal', function(){
    $(this).find('.modal-content')
      .removeClass('animate__zoomOut')
      .addClass('animate__zoomIn');
  });

  $(document).mousemove(function(e) {
    var mouseX = e.pageX, mouseY = e.pageY;
    var moveX = (mouseX - $(window).width()/2) / $(window).width() * 60;
    var moveY = (mouseY - $(window).height()/2) / $(window).height() * 60;
    $('.login-bg-container').css('transform', 'translate(' + moveX + 'px, ' + moveY + 'px)');
    $('.login-bg').css('transform', 'translate(' + -moveX * 1.2 + 'px, ' + -moveY * 1.2 + 'px)');
    $('.modal-dialog').css('transform', 'translate(' + moveX * 0.3 + 'px, ' + moveY * 0.3 + 'px)');
  });

  // Toggle password visibility
  $(document).on('click', '.toggle-password', function () {
    const $input = $(this).closest('.input-group').find('.password-field');
    const type = $input.attr('type') === 'password' ? 'text' : 'password';
    $input.attr('type', type);
    $(this).find('i').toggleClass('fa-eye fa-eye-slash');
  });
});
</script>
</body>
</html>
