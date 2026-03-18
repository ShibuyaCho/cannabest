<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CannaBest - All-in-One Cannabis Sales Ecosystem</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: url('{{ asset('uploads/THC2.png') }}') no-repeat center center fixed;
            background-size: cover;
            color: #495057;
        }
        .hero {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5));
            color: white;
            padding: -20px 0 10px;
            height: 300px;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
        }
        .hero img {
            display: block;
            margin: 0 auto;
            width: auto;
            height: 300px;
        }
        .hero p {
            position: absolute;
            top: 95%;
            left: 50%;
            transform: translate(-50%, -50%);
            margin: 0;
            padding: 0;
            color: white;
            text-align: center;
            font-size: 2vw;
            white-space: nowrap;
        }
        .feature-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        .nav-tabs .nav-link {
            color: #495057;
            font-size: 1.5rem; /* Increase font size */
            padding: 1.5rem; /* Increase padding */
        }
        .tab-content h3 {
        font-size: 2rem; /* Increase font size for headings */
    }

    .tab-content ul li {
        font-size: 1.25rem; /* Increase font size for list items */
    }

    .tab-content p {
        font-size: 1.25rem; /* Increase font size for paragraphs */
    }
        .nav-tabs .nav-link.active {
            font-weight: bold;
            background-color: #e9ecef; /* Add background color for active tab */
            border-color: #dee2e6 #dee2e6 #fff; /* Adjust border color */
        }
        .nav-tabs {
            border-bottom: 2px solid #dee2e6; /* Increase border thickness */
            width: 300px
        }
        .tab-content {
            padding-top: 20px;
        }
        .animate-on-scroll {
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
        }
        .animate-on-scroll.visible {
            opacity: 1;
        }
        .feature:hover {
            transform: scale(1.05);
            transition: transform 0.3s ease;
        }
        .card {
            background-color: rgba(255, 255, 255, 0.84); /* Set the card background color using RGBA */
            padding-bottom: 50px;
        }
        .map-image {
            width: 100%; /* Adjust the width as needed */
            max-width: 300px; /* Set a maximum width */
               height: auto; /* Maintain aspect ratio */
        transition: transform 0.3s ease; /* Add transition for smooth effect */
    }

    .map-image:hover {
        transform: scale(1.05); /* Scale the image on hover */
    }
        @media (max-width: 768px) {
            .hero p {
                font-size: 4.5vw;
                top: 93%;
            }
        }
        .nav-link-logo {
            width: 80px; /* Set the desired width */
            height: 80px; /* Set the desired height */
            background: url('{{ asset('uploads/THC.png') }}') no-repeat center top !important; /* Set the logo as background */
            background-size: cover !important; /* Cover the entire area */
            display: inline-block; /* Ensure it behaves like a button */; 
            overflow: hidden; /* Hide overflow */
                  vertical-align: middle; /* Align text vertically */
    }
     .nav-item {
        display: flex; /* Use flexbox to align items */
        align-items: center; /* Center items vertically */
    }
    .nav-item button {
        display: flex; /* Use flexbox for button */
        align-items: center; /* Center items vertically */
        width: auto; /* Allow button to expand */
        padding: 0.5rem 1rem; /* Add padding for text */
        background: none; /* Remove default button background */
        border: none; /* Remove default button border */
    }
    .nav-item span {
       
        margin-left: 10px; /* Space between image and text */
    }
      .login-button {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        .btn-lg {
    padding: 15px 30px;
    font-size: 1.25rem;
    border-radius: 50px;
    transition: all 0.3s ease;
}

.btn-block {
    display: block;
    width: 100%;
}

.btn-primary {
 background-color:rgb(79, 180, 138);
    border-color:rgb(81, 209, 156);
}

.btn-primary:hover {
   background-color: #1e87db;
    border-color: #45a049;
    transform: translateY(-3px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.btn-success {
    background-color:rgb(79, 180, 138);
    border-color:rgb(81, 209, 156);
}

.btn-success:hover {
    background-color: #1e87db;
     border-color: #45a049;
    transform: translateY(-3px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}
    </style>
</head>
<body>
   
<div class="hero text-center">
    <img src="{{ asset('uploads/THC.png') }}" alt="CannaBest Logo">
    <p class="lead">The Ultimate All-in-One Cannabis Sales Ecosystem</p>
    <div class="d-flex flex-column align-items-end" style="position: absolute; top: 20px; right: 20px;">
        <a href="{{ route('login') }}" class="btn btn-primary mb-2">Employee Login</a>
        <a href="{{ route('wholesale.public-marketplace') }}" class="btn btn-primary">Wholesale Marketplace</a>
    </div>
</div>
<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-md-4">
<a href="{{ url('/retail-marketplace') }}" class="btn btn-success btn-lg btn-block">
  Dispensary Marketplace
</a>
        </div>
    </div>
</div>
 
</div>
</div>
    <div class="container my-5">
        <div class="card">
            <div class="card-body">
                <div class="row mb-5 justify-content-center">
                    <div class="col-md-8 text-center">
                        <h2>Revolutionize Your Cannabis Business</h2>
                        <p class="lead">Cannabest is a comprehensive solution connecting wholesalers, retailers, and customers in one seamless platform. From inventory management to online marketplaces, we've got you covered.</p>
                    </div>
                </div>
       
     <!-- Oregon Specific Section -->
                <div class="row mt-5 justify-content-center">
                    <div class="col-md-4 text-centerc">
                        <img src="{{ asset('uploads/Map.png') }}" alt="Map of Oregon" class="img-fluid map-image">
                    </div>
                    <div class="col-md-8 justify-content-center">
                        <h2 class="text-center">Why Cannabest is Perfect for Oregon</h2>
                        <p class="lead mt-4">Cannabest is tailored to meet the unique needs of the Oregon cannabis market. With full compliance to Oregon's cannabis regulations, our platform ensures seamless operations for businesses in the state.</p>
                        <ul class="list-center">
                            <li>Compliance with Oregon's cannabis laws and regulations</li>
                            <li>Integration with Oregon's METRC system for tracking and reporting</li>
                            <li>Support for local dispensaries and growers</li>
                            <li>Customized solutions for Oregon's unique market dynamics</li>
                        </ul>
                    </div>
                </div>
            </div>
                <div class="row mb-5 justify-content-center">
                    <div class="col-md-4 text-center">
                        <div class="feature animate-on-scroll">
                            <div class="feature-icon">🚀</div>
                            <h3>Integrated Ecosystem</h3>
                            <p>Connect all aspects of your cannabis business in one powerful platform.</p>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="feature animate-on-scroll">
                            <div class="feature-icon">🔗</div>
                            <h3>Seamless Connectivity</h3>
                            <p>Link wholesalers, retailers, and customers for efficient transactions and inventory management.</p>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="feature animate-on-scroll">
                            <div class="feature-icon">📊</div>
                            <h3>Real-time Analytics</h3>
                            <p>Gain valuable insights into your business performance across all levels.</p>
                        </div>
                    </div>
                </div>
    
                <h2 class="text-center mb-4 animate-on-scroll">Benefits for Everyone</h2>

                <div class="row mt-5">
                    <div class="col-md-3 d-flex nav-tabs-container">
<ul class="nav nav-tabs flex-column w-100" id="benefitsTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="wholesaler-tab" data-bs-toggle="tab" data-bs-target="#wholesaler" type="button" role="tab" aria-controls="wholesaler" aria-selected="true">
            <div class="nav-link-logo"></div>
            <span>Wholesaler</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="retailer-tab" data-bs-toggle="tab" data-bs-target="#retailer" type="button" role="tab" aria-controls="retailer" aria-selected="false">
            <div class="nav-link-logo"></div>
            <span>Retailer</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="customer-tab" data-bs-toggle="tab" data-bs-target="#customer" type="button" role="tab" aria-controls="customer" aria-selected="false">
            <div class="nav-link-logo"></div>
            <span>Customer</span>
        </button>
    </li>
</ul>
                        </ul>
                    </div>
                    <div class="col-md-9">
                        <div class="tab-content" id="benefitsTabContent">
                            <div class="tab-pane fade show active" id="wholesaler" role="tabpanel" aria-labelledby="wholesaler-tab">
                                <h3 class="text-center">For Wholesalers</h3>
                                <ul class="list-unstyled text-center">
                                    <li>Streamlined inventory management with METRC integration</li>
                                    <li>Easy product listing and distribution to multiple retailers</li>
                                    <li>Real-time demand forecasting and analytics</li>
                                    <li>Efficient order processing and fulfillment</li>
                                    <li>Direct connection to a network of verified retailers</li>
                                    <li>Automated compliance and reporting tools</li>
                                    <li>Customizable pricing and discount structures</li>
                                    <li>Simplified bulk order management</li>
                                </ul>
                            </div>
                            <div class="tab-pane fade" id="retailer" role="tabpanel" aria-labelledby="retailer-tab">
                                <h3 class="text-center">For Retailers</h3>
                                <ul class="list-unstyled text-center">
                                    <li>Access to a wide range of wholesale products</li>
                                    <li>Customizable online storefronts for B2C sales</li>
                                    <li>Integrated POS system for in-store and online sales</li>
                                    <li>Automatic inventory updates across all channels</li>
                                    <li>Customer relationship management tools</li>
                                    <li>Loyalty program integration</li>
                                    <li>Sales analytics and performance tracking</li>
                                    <li>Streamlined order fulfillment process</li>
                                </ul>
                            </div>
                            <div class="tab-pane fade" id="customer" role="tabpanel" aria-labelledby="customer-tab">
                                <h3 class="text-center">For Customers</h3>
                                <ul class="list-unstyled text-center">
                                    <li>User-friendly online shopping experience</li>
                                    <li>Access to a wide variety of cannabis products and dispensaries</li>
                                    <li>Real-time product availability and pricing</li>
                                    <li>Secure and convenient online ordering</li>
                                    <li>Personalized product recommendations</li>
                                    <li>Loyalty rewards and special offers</li>
                                    <li>Detailed product information and reviews</li>
                                    <li>Multiple fulfillment options (pickup or delivery)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-5 justify-content-center animate-on-scroll">
                    <div class="col-md-6 text-center">
                        <h2>Why Choose Cannabest?</h2>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">Complete METRC integration for compliance</li>
                            <li class="list-group-item">User-friendly interface for all user types</li>
                            <li class="list-group-item">Comprehensive inventory and order management</li>
                            <li class="list-group-item">Customizable online storefronts and marketplaces</li>
                            <li class="list-group-item">Seamless connection between wholesalers and retailers</li>
                            <li class="list-group-item">Advanced analytics and reporting across all levels</li>
                        </ul>
                    </div>
                    <div class="col-md-6 text-center">
                        <h2>Sign Up for Early Access</h2>
                        @if (session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <form action="{{ route('promotional.signup') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="user_type" class="form-label">I am a:</label>
                                <select class="form-control" id="user_type" name="user_type" required>
                                    <option value="wholesaler">Wholesaler</option>
                                    <option value="retailer">Retailer</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="organization_info" class="form-label">Tell us about your organization</label>
                                <textarea class="form-control" id="organization_info" name="organization_info" rows="4" placeholder="Provide some details about your organization..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg">Get Early Access</button>
                        </form>
                    </div>
                </div>
                </div>
                </div>

      

    <footer class="bg-dark text-white text-center py-3 mt-5">
        <p>&copy; 2025 Cannabest. All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function(){
            // Animation on scroll
            function animateOnScroll() {
                $('.animate-on-scroll').each(function() {
                    var elementTop = $(this).offset().top;
                    var elementBottom = elementTop + $(this).outerHeight();
                    var viewportTop = $(window).scrollTop();
                    var viewportBottom = viewportTop + $(window).height();

                    if (elementBottom > viewportTop && elementTop < viewportBottom) {
                        $(this).addClass('visible');
                    }
                });
            }

            // Run on page load
            animateOnScroll();

            // Run on scroll
            $(window).on('scroll', animateOnScroll);

            // Smooth scroll for anchor links
            $('a[href^="#"]').on('click', function(event) {
                var target = $(this.getAttribute('href'));
                if( target.length ) {
                    event.preventDefault();
                    $('html, body').stop().animate({
                        scrollTop: target.offset().top
                    }, 1000);
                }
            });
        });
    </script>
    
</body>
</html>