<!DOCTYPE html>
<html lang="fr">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
	<meta name="description" content="OptiScolaire">
	<meta name="keywords" content="">
	<meta name="author" content="e">
	<meta name="robots" content="">
	<title>OptiScolaire | Gestionnaire d'école Primaire</title>

	<meta name="csrf-token" content="{{ csrf_token() }}">

	<!-- Favicon -->
	<link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/img/logo-small.png') }}">

	<!-- Theme Script js -->
	<script src="{{ asset('assets/js/theme-script.js') }}" type="text/javascript"></script>

	<!-- Bootstrap CSS -->
	<link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }} ">
	<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

	<!-- Feather CSS -->
	<link rel="stylesheet" href="{{ asset('assets/plugins/icons/feather/feather.css') }} ">

	<!-- Tabler Icon CSS -->
	<link rel="stylesheet" href="{{ asset('assets/plugins/tabler-icons/tabler-icons.css') }} ">

	<!-- Daterangepikcer CSS -->
	<link rel="stylesheet" href="{{ asset('assets/plugins/daterangepicker/daterangepicker.css') }} ">

	<!-- Select2 CSS -->
	<link rel="stylesheet" href="{{ asset('assets/plugins/select2/css/select2.min.css') }} ">

	<link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet"/>

	<!-- Fontawesome CSS -->
	<link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/fontawesome.min.css') }} ">
	<link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/all.min.css') }} ">

	<!-- Datetimepicker CSS -->
	<link rel="stylesheet" href="{{ asset('assets/css/bootstrap-datetimepicker.min.css') }} ">

	<!-- Owl Carousel CSS -->
	<link rel="stylesheet" href="{{ asset('assets/plugins/owlcarousel/owl.carousel.min.css') }} ">
	<link rel="stylesheet" href="{{ asset('assets/plugins/owlcarousel/owl.theme.default.min.css') }} ">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

	<!-- Main CSS -->
	<link rel="stylesheet" href="{{ asset('assets/css/style.css') }} ">



</head>

<body>

	<div id="global-loader">
		<div class="page-loader"></div>
	</div>

	<!-- Main Wrapper -->
	<div class="main-wrapper">

		@include('dashboard.layouts.header')
		<!-- /Header -->

		@include('dashboard.layouts.sidebar')

		<!-- Page Wrapper -->
		<div class="page-wrapper">
			<div class="content">

				@yield('content')
				

				
			</div>

		</div>
		<!-- /Page Wrapper -->


	</div>
	<!-- /Main Wrapper -->

	<!-- jQuery -->
	<script src="{{ asset('assets/js/jquery-3.7.1.min.js') }}" type="text/javascript"></script>
	<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

	<!-- Bootstrap Core JS -->
	<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}" type="text/javascript"></script>

	<!-- Daterangepikcer JS -->
	<script src="{{ asset('assets/js/moment.js') }} " type="text/javascript"></script>
	<script src="{{ asset('assets/plugins/daterangepicker/daterangepicker.js') }} " type="text/javascript"></script>
	<script src="{{ asset('assets/js/bootstrap-datetimepicker.min.js') }} " type="text/javascript"></script>

	<!-- Feather Icon JS -->
	<script src="{{ asset('assets/js/feather.min.js') }} " type="text/javascript"></script>

	<!-- Slimscroll JS -->
	<script src="{{ asset('assets/js/jquery.slimscroll.min.js') }} " type="text/javascript"></script>

	<!-- Chart JS -->
	<script src="{{ asset('assets/plugins/apexchart/apexcharts.min.js') }} " type="text/javascript"></script>
	<script src="{{ asset('assets/plugins/apexchart/chart-data.js') }} " type="text/javascript"></script>

	<!-- Owl JS -->
	<script src="{{ asset('assets/plugins/owlcarousel/owl.carousel.min.js') }} " type="text/javascript"></script>

	<!-- Select2 JS -->
	<script src="{{ asset('assets/plugins/select2/js/select2.min.js') }} " type="text/javascript"></script>

	<!-- Counter JS -->
	<script src="{{ asset('assets/plugins/countup/jquery.counterup.min.js') }} " type="text/javascript"></script>
	<script src="{{ asset('assets/plugins/countup/jquery.waypoints.min.js') }} " type="text/javascript">	</script>

	<!-- Custom JS -->
	<script src="{{ asset('assets/js/script.js') }} " type="text/javascript"></script>


<script src="{{ asset('assets/cdn-cgi/scripts/7d0fa10a/cloudflare-static/rocket-loader.min.js') }} " data-cf-settings="65f22dedcdd861c3e81bb152-|49" defer=""></script></body>

<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>


	@yield('scripts')

	<script>
    // Configuration globale de toastr
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: "5000"
    };

    // Gestion des messages Laravel (success / error / validation)
    @if(session('success'))
        toastr.success("{{ session('success') }}");
    @endif

    @if(session('error'))
        toastr.error("{{ session('error') }}");
    @endif

    @if($errors->any())
        @foreach($errors->all() as $error)
            toastr.error("{{ $error }}");
        @endforeach
    @endif
</script>
</html>