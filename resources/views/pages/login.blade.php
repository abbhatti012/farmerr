<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hyrefast</title>
    <link rel="shortcut icon" type="image/png" href="../assets/images/fav.png" />
    <link rel="stylesheet" href="../assets/css/styles.min.css" />
    <link rel="stylesheet" href="../assets/css/custom_style.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <style>
        .position-relative.overflow-hidden.radial-gradient:before {
            background: #fff;
        }
    </style>
</head>

<body>
    <!--  Body Wrapper -->
    <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
        data-sidebar-position="fixed" data-header-position="fixed">

        <div
            class="position-relative overflow-hidden radial-gradient min-vh-100 d-flex align-items-center justify-content-center">
            <div class="d-flex align-items-center justify-content-center w-100">
                <div class="row justify-content-center align-items-center w-100">
                    <div class="col-md-6 col-lg-6 col-xxl-6">
                        <div class="brand-logo logo-position d-flex align-items-center justify-content-between">
                            <a href="#" class="text-nowrap logo-img">
                                <img src="../assets/images/Hyrefast_Logo 2.png" alt="" />
                            </a>
                        </div>
                        <div class="card mb-0 d-flex justify-content-center align-items-center"
                            style="background: transparent;border: 0;box-shadow: none;">
                            <div class="card-body card-body-form">
                                <h2>Log In</h2>
                                @include('utils.show_error')
                                @include('utils.show_success')
                                <form  action="{{ route('admin-login') }}" method="POST">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="emailInput" class="form-label">Email address</label>
                                        <input type="email" class="form-control" id="emailInput" name="email"
                                            placeholder="Enter your email address..." required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="passwordInput" class="form-label">Password</label>
                                        <input type="password" class="form-control" id="passwordInput" name="password"
                                            placeholder="Enter your password..." required>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100 py-8 fs-4 mb-4 rounded-2">Log
                                        In</button>
                                </form>


                                <div class="modal fade" id="exampleModal" tabindex="-1"
                                    aria-labelledby="exampleModalLabel" aria-hidden="true">
                                    <div class="modal-dialog pb-3">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                    aria-label="Close"></button>
                                            </div>
                                            <div class="d-flex justify-content-center">
                                                <div
                                                    class="sms-image bg-primary p-3 rounded-5 d-flex justify-content-center">
                                                    <img src="../assets/images/sms.png" alt="">
                                                </div>
                                            </div>
                                            <div class="modal-body">
                                                <div>
                                                    <p class="fs-3">Verification Code</p>
                                                </div>
                                                <div>
                                                    <p class="fs-2 ">We have sent the OTP code to xyz@gmail.com for the
                                                        Login process.</p>
                                                </div>
                                                <div>
                                                    <p class="">Enter The Code</p>
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-center">
                                                <form>
                                                    <input id="otp1" class="otp-input" type="text"
                                                        maxlength="1" pattern="[0-9]"
                                                        title="Please enter only numeric digits"
                                                        oninput="moveToNext(this)">
                                                    <input id="otp2" class="otp-input" type="text"
                                                        maxlength="1" pattern="[0-9]"
                                                        title="Please enter only numeric digits"
                                                        oninput="moveToNext(this)">
                                                    <input id="otp3" class="otp-input" type="text"
                                                        maxlength="1" pattern="[0-9]"
                                                        title="Please enter only numeric digits"
                                                        oninput="moveToNext(this)">
                                                    <input id="otp4" class="otp-input" type="text"
                                                        maxlength="1" pattern="[0-9]"
                                                        title="Please enter only numeric digits"
                                                        oninput="moveToNext(this)">
                                                </form>
                                            </div>
                                            <div>
                                                <p class="fs-2 text-primary text-center pt-3"><u>Resend Code</u></p>
                                            </div>
                                            <div class="d-flex justify-content-center mb-5">
                                                <button type="submit" class="btn btn-primary w-75"> Submit</button>
                                            </div>
                                            <!-- <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="button" class="btn btn-primary">Save changes</button>
                                                </div> -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-6 col-xxl-6 my-3">
                        <div class="card mb-0" style="background: #533FF0; height: 95vh;"">
                            <div class="card-body"
                                style="display: flex;align-items: center;vertical-align: middle;">
                                <img src="../assets/images/admin_login.png" alt="">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        function moveToNext(currentInput) {
            if (currentInput.value.length >= 1) {
                var nextInput = currentInput.nextElementSibling;
                if (nextInput !== null) {
                    nextInput.focus();
                }
            }
        }
    </script>
    <script src="../assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
