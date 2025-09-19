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

		<!-- Add Class Routine -->
		<div class="modal fade" id="add_class_routine">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-wrapper">
						<div class="modal-header">
							<h4 class="modal-title">Add Class Routine</h4>
							<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
								<i class="ti ti-x"></i>
							</button>
						</div>
						<form action="#">
							<div class="modal-body">
								<div class="row">
									<div class="col-md-12">
										<div class="mb-3">
											<label class="form-label">Teacher</label>
											<select class="select">
												<option>Select</option>
												<option>Erickson</option>
												<option>Mori</option>
												<option>Joseph</option>
												<option>James</option>
											</select>
										</div>
										<div class="mb-3">
											<label class="form-label">Class</label>
											<select class="select">
												<option>Select</option>
												<option>I</option>
												<option>II</option>
												<option>III</option>
												<option>IV</option>
											</select>
										</div>
										<div class="mb-3">
											<label class="form-label">Section</label>
											<select class="select">
												<option>Select</option>
												<option>A</option>
												<option>B</option>
												<option>C</option>
											</select>
										</div>
										<div class="mb-3">
											<label class="form-label">Day</label>
											<select class="select">
												<option>Select</option>
												<option>Monday</option>
												<option>Tuesday</option>
												<option>Wedneshday</option>
												<option>Thursday</option>
												<option>Friday</option>
											</select>
										</div>
										<div class="row">
											<div class="col-md-6">
												<div class="mb-3">
													<label class="form-label">Start Time</label>
													<div class="date-pic">
														<input type="text" class="form-control timepicker" placeholder="Choose">
														<span class="cal-icon"><i class="ti ti-clock"></i></span>
													</div>
												</div>
											</div>
											<div class="col-md-6">
												<div class="mb-3">
													<label class="form-label">End Time</label>
													<div class="date-pic">
														<input type="text" class="form-control timepicker" placeholder="Choose">
														<span class="cal-icon"><i class="ti ti-clock"></i></span>
													</div>
												</div>
											</div>
										</div>
										<div class="mb-3">
											<label class="form-label">Class Room</label>
											<select class="select">
												<option>Select</option>
												<option>101</option>
												<option>102</option>
												<option>103</option>
												<option>104</option>
												<option>105</option>
											</select>
										</div>
										<div class="modal-satus-toggle d-flex align-items-center justify-content-between">
											<div class="status-title">
												<h5>Status</h5>
												<p>Change the Status by toggle </p>
											</div>
											<div class="status-toggle modal-status">
												<input type="checkbox" id="user1" class="check">
												<label for="user1" class="checktoggle"> </label>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="modal-footer">
								<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
								<button type="submit" class="btn btn-primary">Add Class Routine</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		<!-- /Add Class Routine -->

		<!-- Add Event -->
		<div class="modal fade" id="add_event">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">New Event</h4>
						<button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
							<i class="ti ti-x"></i>
						</button>
					</div>
					<form action="#">
						<div class="modal-body">
							<div class="row">
								<div class="col-md-12">
									<div>
										<label class="form-label">Pour</label>
										<div class="d-flex align-items-center flex-wrap">
											<div class="form-check me-3 mb-3">
												<input class="form-check-input" type="radio" name="event" id="all" checked="">
												<label class="form-check-label" for="all">
													All
												</label>
											</div>
											<div class="form-check me-3 mb-3">
												<input class="form-check-input" type="radio" name="event" id="students">
												<label class="form-check-label" for="students">
													Students
												</label>
											</div>
											<div class="form-check me-3 mb-3">
												<input class="form-check-input" type="radio" name="event" id="staffs">
												<label class="form-check-label" for="staffs">
													Staffs
												</label>
											</div>
										</div>
									</div>
									<div class="all-content" id="all-student">
										<div class="mb-3">
											<label class="form-label">Classes</label>
											<select class="select">
												<option>All Classes</option>
												<option>I</option>
												<option>II</option>
												<option>III</option>
												<option>IV</option>
											</select>
										</div>
										<div class="mb-3">
											<label class="form-label">Sections</label>
											<select class="select">
												<option>All Sections</option>
												<option>A</option>
												<option>B</option>
											</select>
										</div>
									</div>
									<div class="all-content" id="all-staffs">
										<div class="mb-3">
											<div class="bg-light-500 p-3 pb-2 rounded">
												<label class="form-label">Role</label>
												<div class="row">
													<div class="col-md-6">
														<div class="form-check form-check-sm mb-2">
															<input class="form-check-input" type="checkbox">Admin
														</div>
														<div class="form-check form-check-sm mb-2">
															<input class="form-check-input" type="checkbox" checked="">Teacher
														</div>
														<div class="form-check form-check-sm mb-2">
															<input class="form-check-input" type="checkbox">Driver
														</div>
													</div>
													<div class="col-md-6">
														<div class="form-check form-check-sm mb-2">
															<input class="form-check-input" type="checkbox">Accountant
														</div>
														<div class="form-check form-check-sm mb-2">
															<input class="form-check-input" type="checkbox">Librarian
														</div>
														<div class="form-check form-check-sm mb-2">
															<input class="form-check-input" type="checkbox">Receptionist
														</div>
													</div>
												</div>
											</div>
										</div>
										<div class="mb-3">
											<label class="form-label">All Teachers</label>
											<select class="select">
												<option>Select</option>
												<option>I</option>
												<option>II</option>
												<option>III</option>
												<option>IV</option>
											</select>
										</div>
									</div>
								</div>
								<div class="mb-3">
									<label class="form-label">Titre de l'évènement</label>
									<input type="text" class="form-control" placeholder="Enter Title">
								</div>
								<div class="mb-3">
									<label class="form-label">Catégorie</label>
									<select class="select">
										<option>Select</option>
										<option>Celebration</option>
										<option>Training</option>
										<option>Meeting</option>
										<option>Holidays</option>
									</select>
								</div>
								<div class="col-md-6">
									<div class="mb-3">
										<label class="form-label">Date de debut</label>
										<div class="date-pic">
											<input type="text" class="form-control datetimepicker" placeholder="15 May 2024">
											<span class="cal-icon"><i class="ti ti-calendar"></i></span>
										</div>
									</div>
								</div>
								<div class="col-md-6">
									<div class="mb-3">
										<label class="form-label">Date de fin</label>
										<div class="date-pic">
											<input type="text" class="form-control datetimepicker" placeholder="21 May 2024">
											<span class="cal-icon"><i class="ti ti-calendar"></i></span>
										</div>
									</div>
								</div>
								<div class="col-md-6">
									<div class="mb-3">
										<label class="form-label">Heure de debut</label>
										<div class="date-pic">
											<input type="text" class="form-control timepicker" placeholder="09:10 AM">
											<span class="cal-icon"><i class="ti ti-clock"></i></span>
										</div>
									</div>
								</div>
								<div class="col-md-6">
									<div class="mb-3">
										<label class="form-label">Heure de fin</label>
										<div class="date-pic">
											<input type="text" class="form-control timepicker" placeholder="12:50 PM">
											<span class="cal-icon"><i class="ti ti-clock"></i></span>
										</div>
									</div>
								</div>
								<div class="col-md-12">
									<div class="mb-3">
										<div class="bg-light p-3 pb-2 rounded">
											<div class="mb-3">
												<label class="form-label">Joindre un fichier</label>
												<p>Taille de téléchargement de 4 Mo, format PDF accepté</p>
											</div>
											<div class="d-flex align-items-center flex-wrap">
												<div class="btn btn-primary drag-upload-btn mb-2 me-2">
													<i class="ti ti-file-upload me-1"></i>Charger
													<input type="file" class="form-control image_sign" multiple="">
												</div>
												<p class="mb-2">Doc.pdf</p>
											</div>
										</div>
									</div>
									<div class="mb-0">
										<label class="form-label">Message</label>
										<textarea class="form-control" rows="4">Réunion avec le personnel sur l'amélioration de la qualité de formation</textarea>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Annuler</a>
							<button type="submit" class="btn btn-primary">Sauvegarder</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /Add Event -->

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