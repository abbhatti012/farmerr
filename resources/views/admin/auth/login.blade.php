<!DOCTYPE html>

<html lang="en">

<meta http-equiv="content-type" content="text/html;charset=UTF-8" />

<head>
<title>Farmerr</title>
<link rel="shortcut icon" href="{{ asset('assets/admin/media/logos/farmerr.fav.webp') }}" />
    <meta charset="utf-8" />

    <link rel="canonical" href="sign-in.html" />
    <link rel="shortcut icon" href="{{ asset('admin/media/logos/favicon.ico') }}" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />

    <link href="{{ asset('assets/admin/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/admin/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />

    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-37564768-1"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());
        gtag('config', 'UA-37564768-1');
    </script>
    <script>
        if (window.top != window.self) {
            window.top.location.replace(window.self.location.href);
        }
    </script>
    <style>
        .password-wrapper {
            position: relative;
        }
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }
        .password-toggle:hover {
            color: #000;
        }
        .input-group .form-control {
            border-radius: 0.375rem; /* Match Bootstrap's default border radius */
        }
    </style>
</head>

<body id="kt_body" class="app-blank bgi-size-cover bgi-attachment-fixed bgi-position-center">
    <script>
        var defaultThemeMode = "light";
        var themeMode;

        if (document.documentElement) {
            if (document.documentElement.hasAttribute("data-bs-theme-mode")) {
                themeMode = document.documentElement.getAttribute("data-bs-theme-mode");
            } else {
                if (localStorage.getItem("data-bs-theme") !== null) {
                    themeMode = localStorage.getItem("data-bs-theme");
                } else {
                    themeMode = defaultThemeMode;
                }
            }

            if (themeMode === "system") {
                themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
            }

            document.documentElement.setAttribute("data-bs-theme", themeMode);
        }
    </script>
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-5FS8GGP" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>

    <div class="d-flex flex-column flex-root" id="kt_app_root">
        <style>
           body {
                background-image: url('{{asset('public/assets/admin/media/auth/two-beautiful-bouquets-flowers-grey-surface 1.webp') }}');
            }

            [data-bs-theme="dark"] body {
                background-image: url('{{ asset('public/assets/admin/media/auth/two-beautiful-bouquets-flowers-grey-surface 1.webp') }}');
            }
        </style>
        <div class="d-flex flex-column flex-lg-row flex-column-fluid">
            {{-- <div class="d-flex flex-lg-row-fluid">
                <div class="d-flex flex-column flex-center pb-0 pb-lg-10 p-10 w-100 ">
                <img class="theme-light-show mx-auto mw-100 w-150px w-lg-300px mb-10 mb-lg-20" src="{{ asset('public/assets/admin/media/auth/agency.png') }}" alt="main_banner" />
                <img class="theme-dark-show mx-auto mw-100 w-150px w-lg-300px mb-10 mb-lg-20" src="{{ asset('public/assets/admin/media/auth/agency-dark.png') }}" alt="main_banner" />


                    <!-- <h1 class="text-gray-800 fs-2qx fw-bold text-center mb-7">
                        Fast, Efficient and Productive
                    </h1>

                    <div class="text-gray-600 fs-base text-center fw-semibold">
                        In this kind of post, <a href="#" class="opacity-75-hover text-primary me-1">the blogger</a>

                        introduces a person they’ve interviewed <br /> and provides some background information about

                        <a href="#" class="opacity-75-hover text-primary me-1">the interviewee</a>
                        and their <br /> work following this is a transcript of the interview.
                    </div> -->
                </div>
            </div> --}}

            <div class="d-flex align-items-center flex-column-fluid flex-lg-row-auto justify-content-center p-12 mx-auto">
                <div class="bg-body d-flex flex-column flex-center rounded-4 w-md-600px p-10">
                    <div class=" flex-center flex-column align-items-stretch h-lg-100 w-md-400px">
                        <div class=" flex-center flex-column flex-column-fluid ">
                            @include('utils.show_success')
                            @include('utils.show_error')
                            <form class="form w-100" novalidate="novalidate" id="kt_sign_in_form" method="POST" action="{{ route('admin.login.submit') }}">
                                @csrf <!-- Add CSRF token -->
                                <div class="text-center mb-11">
                                    <img class="theme-light-show mx-auto mw-100 w-100px w-lg-100px mb-10 mb-lg-20" src="{{ asset('public/assets/admin/media/logos/Farmerr_logo.svg') }}" alt="main_banner" />
                                    <h1 class="text-gray-900 fw-bolder mb-3">Sign In</h1>
                                    <!-- <div class="text-gray-500 fw-semibold fs-6">Your Social Campaigns</div> -->
                                </div>

                                <div class="fv-row mb-8">
                                    <input type="text" placeholder="Email" name="email" autocomplete="off" class="form-control bg-transparent" />
                                </div>

                                <div class="fv-row mb-8 password-wrapper">
                                    <input type="password" placeholder="Password" name="password" autocomplete="off" class="form-control bg-transparent" id="password" />
                                    <i class="fa fa-eye password-toggle" id="togglePassword"></i>
                                </div>
                                <!-- <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8">
                                    <div></div>
                                    <a href="reset-password.html" class="link-primary">Forgot Password ?</a>
                                </div> -->

                                <div class="d-grid mb-10 pt-5">
                                    <button type="submit" id="kt_sign_in_submit" class="btn btn-primary">
                                        <span class="indicator-label">Sign In</span>
                                        <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                    </button>
                                </div>

                                <!-- <div class="text-gray-500 text-center fw-semibold fs-6">
                                    Not a Member yet? <a href="sign-up.html" class="link-primary">Sign up</a>
                                </div> -->
                            </form>


                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script src="{{ asset('public/assets/admin/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('public/assets/admin/js/scripts.bundle.js') }}"></script>

    <script>
        "use strict";
        var KTSigninGeneral = (function() {
            var t, e, r;
            return {
                init: function() {
                    (t = document.querySelector("#kt_sign_in_form")),
                    (e = document.querySelector("#kt_sign_in_submit")),
                    (r = FormValidation.formValidation(t, {
                        fields: {
                            email: {
                                validators: {
                                    regexp: {
                                        regexp: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
                                        message: "The value is not a valid email address",
                                    },
                                    notEmpty: {
                                        message: "Email address is required",
                                    },
                                },
                            },
                            password: {
                                validators: {
                                    notEmpty: {
                                        message: "The password is required",
                                    },
                                },
                            },
                        },
                        plugins: {
                            trigger: new FormValidation.plugins.Trigger(),
                            bootstrap: new FormValidation.plugins.Bootstrap5({
                                rowSelector: ".fv-row",
                                eleInvalidClass: "",
                                eleValidClass: "",
                            }),
                        },
                    })),
                    e.addEventListener("click", function(i) {
                        i.preventDefault(),
                            r.validate().then(function(r) {
                                if ("Valid" == r) {
                                    // Submit the form
                                    t.submit();
                                } else {
                                    Swal.fire({
                                        text: "Sorry, looks like there are some errors detected, please try again.",
                                        icon: "error",
                                        buttonsStyling: !1,
                                        confirmButtonText: "Ok, got it!",
                                        customClass: {
                                            confirmButton: "btn btn-primary",
                                        },
                                    });
                                }
                            });
                    });
                },
            };
        })();
        KTUtil.onDOMContentLoaded(function() {
            KTSigninGeneral.init();
        });
        // Password toggle functionality
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordField = document.getElementById('password');
            const toggleIcon = this;
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        });
    </script>

    <script>
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
</body>

</html>