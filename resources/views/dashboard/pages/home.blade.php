@extends('dashboard.layouts.master')
@section('content')
	<!-- Page Header -->
				<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
					<div class="my-auto mb-2">
						<h3 class="page-title mb-1">Tableau de Bord Admin</h3>
						<nav>
							<ol class="breadcrumb mb-0">
								<li class="breadcrumb-item">
									<a href="#">Tableau de Bord</a>
								</li>
								<li class="breadcrumb-item active" aria-current="page">Admin</li>
							</ol>
						</nav>
					</div>
					<div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
						<div class="mb-2">
							<a href="{{ route('eleves.create') }}" class="btn btn-primary d-flex align-items-center me-3"><i class="ti ti-square-rounded-plus me-2"></i>Ajouter un Eleve</a>
						</div>
						<div class="mb-2">
							<a href="{{ route('reglements.index') }}" class="btn btn-light d-flex align-items-center">Faire un Règlement</a>
						</div>
					</div>
				</div>
				<!-- /Page Header -->

				<div class="row">
					<div class="col-md-12">
						

						<!-- Dashboard Content -->
						<div class="card bg-dark">
							<div class="overlay-img">
								<img src="assets/img/bg/shape-04.png" alt="img" class="img-fluid shape-01">
								<img src="assets/img/bg/shape-01.png" alt="img" class="img-fluid shape-02">
								<img src="assets/img/bg/shape-02.png" alt="img" class="img-fluid shape-03">
								<img src="assets/img/bg/shape-03.png" alt="img" class="img-fluid shape-04">
							</div>
							<div class="card-body">
								<div class="d-flex align-items-xl-center justify-content-xl-between flex-xl-row flex-column">
									<div class="mb-3 mb-xl-0">
										<div class="d-flex align-items-center flex-wrap mb-2">
										<h1 class="text-white me-2">Bienvenue, {{ $user->name }}</h1>

											<a href="#" class="avatar avatar-sm img-rounded bg-gray-800 dark-hover"><i class="ti ti-edit text-white"></i></a>
										</div>
										<p class="text-white">Passez une bonne journée au travail</p>
									</div>
									<p class="text-white">
										<i class="ti ti-refresh me-1"></i>
										Mise à jour récente le {{ \Carbon\Carbon::parse($user->updated_at)->translatedFormat('d F Y') }}
									</p>

								</div>
							</div>
						</div>
						<!-- /Dashboard Content -->

					</div>
				</div>

				<div class="row">

					<div class="col-xxl-3 col-sm-6 d-flex">
						<div class="card flex-fill animate-card border-0">
							<div class="card-body">
								<div class="d-flex align-items-center">
									<div class="avatar avatar-xl bg-danger-transparent me-2 p-1">
										<img src="assets/img/icons/student.svg" alt="img">
									</div>
									<div class="overflow-hidden flex-fill">
										<div class="d-flex align-items-center justify-content-between">
											<h2 class="counter">{{ $totalEleves }}</h2>
											<span class="badge bg-danger">1.2%</span>
										</div>
										<p>Total Eleves</p>
									</div>
								</div>
								
							</div>
						</div>
					</div>

					<div class="col-xxl-3 col-sm-6 d-flex">
						<div class="card flex-fill animate-card border-0">
							<div class="card-body">
								<div class="d-flex align-items-center">
									<div class="avatar avatar-xl me-2 bg-secondary-transparent p-1">
										<img src="assets/img/icons/teacher.svg" alt="img">
									</div>
									<div class="overflow-hidden flex-fill">
										<div class="d-flex align-items-center justify-content-between">
											<h2 class="counter">{{ $totalInscriptions }}</h2>
											<span class="badge bg-skyblue">1.2%</span>
										</div>
										
										<p>Total des inscriptions cette année</p>
									</div>
								</div>
								
							</div>
						</div>
					</div>

					<div class="col-xxl-3 col-sm-6 d-flex">
						<div class="card flex-fill animate-card border-0">
							<div class="card-body">
								<div class="d-flex align-items-center">
									<div class="avatar avatar-xl me-2 bg-warning-transparent p-1">
										<img src="{{ asset('assets/img/icons/money-bag.svg') }}" alt="img">
									</div>
									<div class="overflow-hidden flex-fill">
										<div class="d-flex align-items-center justify-content-between">
											<h2>{{ number_format($fraisStats['frais_attendus'], 0, ',', ' ') }}</h2>
											<span class="badge bg-warning">{{ $fraisStats['evolution_frais'] }}%</span>
										</div>
										<p>Total des frais Attendu</p>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="col-xxl-3 col-sm-6 d-flex">
						<div class="card flex-fill animate-card border-0">
							<div class="card-body">
								<div class="d-flex align-items-center">
									<div class="avatar avatar-xl me-2 bg-success-transparent p-1">
										<img src="{{ asset('assets/img/icons/cash.svg') }}" alt="img">
									</div>
									<div class="overflow-hidden flex-fill">
										<div class="d-flex align-items-center justify-content-between">
											<h2>{{ number_format($fraisStats['frais_percus'], 0, ',', ' ') }}</h2>
											<span class="badge bg-success">{{ $fraisStats['pourcentage_perception'] }}%</span>
										</div>
										<p>Total des frais perçus</p>
									</div>
								</div>
							</div>
						</div>
					</div>

				</div>

				{{-- <div class="row">

					<!-- Schedules -->
					<div class="col-xxl-4 col-xl-6 col-md-12 d-flex">
						<div class="card flex-fill">
							<div class="card-header d-flex align-items-center justify-content-between">
								<div>
									<h4 class="card-title">Calendrier</h4>
								</div>
								<a href="#" class="link-primary fw-medium me-2" data-bs-toggle="modal" data-bs-target="#add_event"><i class="ti ti-square-plus me-1"></i>Add New</a>
							</div>
							<div class="card-body">
								<div class="datepic mb-4"></div>
								<h5 class="mb-3">Événements à venir</h5>
								<div class="event-wrapper event-scroll">
									<!-- Event Item -->
									<div class="border-start border-skyblue border-3 shadow-sm p-3 mb-3">
										<div class="d-flex align-items-center mb-3 pb-3 border-bottom">
											<span class="avatar p-1 me-2 bg-teal-transparent flex-shrink-0">
												<i class="ti ti-user-edit text-info fs-20"></i>
											</span>
											<div class="flex-fill">
												<h6 class="mb-1">Rencontre parents-enseignants</h6>
												<p class="d-flex align-items-center"><i class="ti ti-calendar me-1"></i>15 July 2024</p>
											</div>
										</div>
										<div class="d-flex align-items-center justify-content-between">
											<p class="mb-0"><i class="ti ti-clock me-1"></i>09:10AM - 10:50PM</p>
											<div class="avatar-list-stacked avatar-group-sm">
												<span class="avatar border-0">
													<img src="assets/img/parents/parent-01.jpg" class="rounded-circle" alt="img">
												</span>
												<span class="avatar border-0">
													<img src="assets/img/parents/parent-07.jpg" class="rounded-circle" alt="img">
												</span>
												<span class="avatar border-0">
													<img src="assets/img/parents/parent-02.jpg" class="rounded-circle" alt="img">
												</span>
											</div>
										</div>
									</div>
									<!-- /Event Item -->

									<!-- Event Item -->
									<div class="border-start border-info border-3 shadow-sm p-3 mb-3">
										<div class="d-flex align-items-center mb-3 pb-3 border-bottom">
											<span class="avatar p-1 me-2 bg-info-transparent flex-shrink-0">
												<i class="ti ti-user-edit fs-20"></i>
											</span>
											<div class="flex-fill">
												<h6 class="mb-1">Rencontre parents-enseignants</h6>
												<p class="d-flex align-items-center"><i class="ti ti-calendar me-1"></i>15 July 2024</p>
											</div>
										</div>
										<div class="d-flex align-items-center justify-content-between">
											<p class="mb-0"><i class="ti ti-clock me-1"></i>09:10AM - 10:50PM</p>
											<div class="avatar-list-stacked avatar-group-sm">
												<span class="avatar border-0">
													<img src="assets/img/parents/parent-05.jpg" class="rounded-circle" alt="img">
												</span>
												<span class="avatar border-0">
													<img src="assets/img/parents/parent-06.jpg" class="rounded-circle" alt="img">
												</span>
												<span class="avatar border-0">
													<img src="assets/img/parents/parent-07.jpg" class="rounded-circle" alt="img">
												</span>
											</div>
										</div>
									</div>
									<!-- /Event Item -->

									<!-- Event Item -->
									<div class="border-start border-danger border-3 shadow-sm p-3 mb-3">
										<div class="d-flex align-items-center mb-3 pb-3 border-bottom">
											<span class="avatar p-1 me-2 bg-danger-transparent flex-shrink-0">
												<i class="ti ti-vacuum-cleaner fs-24"></i>
											</span>
											<div class="flex-fill">
												<h6 class="mb-1">Réunion de vacances</h6>
												<p class="d-flex align-items-center"><i class="ti ti-calendar me-1"></i>07 July 2024 - 07 July 2024</p>
											</div>
										</div>
										<div class="d-flex align-items-center justify-content-between">
											<p class="mb-0"><i class="ti ti-clock me-1"></i>09:10 AM - 10:50 PM</p>
											<div class="avatar-list-stacked avatar-group-sm">
												<span class="avatar border-0">
													<img src="assets/img/parents/parent-11.jpg" class="rounded-circle" alt="img">
												</span>
												<span class="avatar border-0">
													<img src="assets/img/parents/parent-13.jpg" class="rounded-circle" alt="img">
												</span>
											</div>
										</div>
									</div>
									<!-- /Event Item -->

								</div>
							</div>
						</div>
					</div>
					<!-- /Schedules -->

					<!-- Attendance -->
					<div class="col-xxl-4 col-xl-6 col-md-12 d-flex flex-column">

						<div class="card">
							<div class="card-header d-flex align-items-center justify-content-between">
								<h4 class="card-title">Bilan du mois</h4>
								
							</div>
							<div class="card-body">
								<div class="list-tab mb-4">
									<ul class="nav">
										<li>
											<a href="#" class="active" data-bs-toggle="tab" data-bs-target="#students">Scolarité</a>
										</li>
										<li>
											<a href="#" data-bs-toggle="tab" data-bs-target="#teachers">Transport</a>
										</li>
										<li>
											<a href="#" data-bs-toggle="tab" data-bs-target="#staff">Cantine</a>
										</li>
                                        
									</ul>
								</div>
								<div class="tab-content">
									<div class="tab-pane fade active show" id="students">
										<div class="row gx-3">
											<div class="col-sm-4">
												<div class="card bg-light-300 shadow-none border-0">
													<div class="card-body p-3 text-center">
														<h5>28</h5>
														<p class="fs-12">Emergency</p>
													</div>
												</div>
											</div>
											<div class="col-sm-4">
												<div class="card bg-light-300 shadow-none border-0">
													<div class="card-body p-3 text-center">
														<h5>01</h5>
														<p class="fs-12">Absent</p>
													</div>
												</div>
											</div>
											<div class="col-sm-4">
												<div class="card bg-light-300 shadow-none border-0">
													<div class="card-body p-3 text-center">
														<h5>01</h5>
														<p class="fs-12">Late</p>
													</div>
												</div>
											</div>
										</div>
										<div class="text-center">
											<div id="student-chart" class="mb-4"></div>
											<a href="student-attendance.html" class="btn btn-light"><i class="ti ti-calendar-share me-1"></i>View All</a>
										</div>
									</div>
									<div class="tab-pane fade" id="teachers">
										<div class="row gx-3">
											<div class="col-sm-4">
												<div class="card bg-light-300 shadow-none border-0">
													<div class="card-body p-3 text-center">
														<h5>30</h5>
														<p class="fs-12">Emergency</p>
													</div>
												</div>
											</div>
											<div class="col-sm-4">
												<div class="card bg-light-300 shadow-none border-0">
													<div class="card-body p-3 text-center">
														<h5>03</h5>
														<p class="fs-12">Absent</p>
													</div>
												</div>
											</div>
											<div class="col-sm-4">
												<div class="card bg-light-300 shadow-none border-0">
													<div class="card-body p-3 text-center">
														<h5>03</h5>
														<p class="fs-12">Late</p>
													</div>
												</div>
											</div>
										</div>
										<div class="text-center">
											<div id="teacher-chart" class="mb-4"></div>
											<a href="teacher-attendance.html" class="btn btn-light"><i class="ti ti-calendar-share me-1"></i>View All</a>
										</div>
									</div>
									<div class="tab-pane fade" id="staff">
										<div class="row gx-3">
											<div class="col-sm-4">
												<div class="card bg-light-300 shadow-none border-0">
													<div class="card-body p-3 text-center">
														<h5>45</h5>
														<p class="fs-12">Emergency</p>
													</div>
												</div>
											</div>
											<div class="col-sm-4">
												<div class="card bg-light-300 shadow-none border-0">
													<div class="card-body p-3 text-center">
														<h5>01</h5>
														<p class="fs-12">Absent</p>
													</div>
												</div>
											</div>
											<div class="col-sm-4">
												<div class="card bg-light-300 shadow-none border-0">
													<div class="card-body p-3 text-center">
														<h5>10</h5>
														<p class="fs-12">Late</p>
													</div>
												</div>
											</div>
										</div>
										<div class="text-center">
											<div id="staff-chart" class="mb-4"></div>
											<a href="staff-attendance.html" class="btn btn-light"><i class="ti ti-calendar-share me-1"></i>View All</a>
										</div>
									</div>
								</div>

							</div>
						</div>

						<div class="row flex-fill">

							<!-- Best Performer -->
							<div class="col-sm-6 d-flex flex-column">
								<div class="bg-success-800 p-3 br-5 text-center flex-fill mb-4 pb-0  owl-height bg-01">
									<div class="owl-carousel student-slider h-100">
										<div class="item h-100">
											<div class="d-flex justify-content-between flex-column h-100">
												<div>
													<h5 class="mb-3 text-white">Meilleur de classe</h5>
													<h4 class="mb-1 text-white">Rubell</h4>
													<p class="text-light">CP1</p>
												</div>
												<img src="assets/img/performer/performer-01.png" alt="img">
											</div>
										</div>
										<div class="item h-100">
											<div class="d-flex justify-content-between flex-column h-100">
												<div>
													<h5 class="mb-3 text-white">Meilleur de classe</h5>
													<h4 class="mb-1 text-white">George Odell</h4>
													<p class="text-light">CP2</p>
												</div>
												<img src="assets/img/performer/performer-02.png" alt="img">
											</div>
										</div>
									</div>
								</div>
							</div>
							<!-- /Best Performer -->

							<!-- Star Students -->
							<div class="col-sm-6 d-flex flex-column">
								<div class="bg-info p-3 br-5 text-center flex-fill mb-4 pb-0 owl-height bg-02">
									<div class="owl-carousel teacher-slider h-100">
										<div class="item h-100">
											<div class="d-flex justify-content-between flex-column h-100">
												<div>
													<h5 class="mb-3 text-white">SMeilleur de classe</h5>
													<h4 class="mb-1 text-white">Tenesa</h4>
													<p class="text-light">CE1</p>
												</div>
												<img src="assets/img/performer/student-performer-01.png" alt="img">
											</div>
										</div>
										<div class="item h-100">
											<div class="d-flex justify-content-between flex-column h-100">
												<div>
													<h5 class="mb-3 text-white">Meilleur de classe</h5>
													<h4 class="mb-1 text-white">Michael </h4>
													<p>CM2</p>
												</div>
												<img src="assets/img/performer/student-performer-02.png" alt="img">
											</div>
										</div>
									</div>
								</div>
							</div>
							<!-- /Star Students -->

						</div>

					</div>
					<!-- /Attendance -->

					

				</div> --}}

				

				<div class="row">

					<!-- Links -->
					<div class="col-xl-3 col-md-6 d-flex">
						<a href="#" class="card bg-warning-transparent border border-5 border-white animate-card flex-fill">
							<div class="card-body">
								<div class="d-flex align-items-center justify-content-between">
									<div class="d-flex align-items-center">
										<span class="avatar avatar-lg bg-warning rounded flex-shrink-0 me-2"><i class="ti ti-calendar-share fs-24"></i></span>
										<div class="overflow-hidden">
											<h6 class="fw-semibold text-default">Lien</h6>
										</div>
									</div>
									<span class="btn btn-white warning-btn-hover avatar avatar-sm p-0 flex-shrink-0 rounded-circle"><i class="ti ti-chevron-right fs-14"></i></span>
								</div>
							</div>
						</a>
					</div>
					<!-- /Links -->

					<!-- Links -->
					<div class="col-xl-3 col-md-6 d-flex">
						<a href="#" class="card bg-success-transparent border border-5 border-white animate-card flex-fill ">
							<div class="card-body">
								<div class="d-flex align-items-center justify-content-between">
									<div class="d-flex align-items-center">
										<span class="avatar avatar-lg bg-success rounded flex-shrink-0 me-2"><i class="ti ti-speakerphone fs-24"></i></span>
										<div class="overflow-hidden">
											<h6 class="fw-semibold text-default">Lien</h6>
										</div>
									</div>
									<span class="btn btn-white success-btn-hover avatar avatar-sm p-0 flex-shrink-0 rounded-circle"><i class="ti ti-chevron-right fs-14"></i></span>
								</div>
							</div>
						</a>
					</div>
					<!-- /Links -->

					<!-- Links -->
					<div class="col-xl-3 col-md-6 d-flex">
						<a href="#" class="card bg-danger-transparent border border-5 border-white animate-card flex-fill">
							<div class="card-body">
								<div class="d-flex align-items-center justify-content-between">
									<div class="d-flex align-items-center">
										<span class="avatar avatar-lg bg-danger rounded flex-shrink-0 me-2"><i class="ti ti-sphere fs-24"></i></span>
										<div class="overflow-hidden">
											<h6 class="fw-semibold text-default">Lien</h6>
										</div>
									</div>
									<span class="btn btn-white avatar avatar-sm p-0 flex-shrink-0 rounded-circle danger-btn-hover"><i class="ti ti-chevron-right fs-14"></i></span>
								</div>
							</div>
						</a>
					</div>
					<!-- /Links -->

					<!-- Links -->
					<div class="col-xl-3 col-md-6 d-flex">
						<a href="#" class="card bg-secondary-transparent border border-5 border-white animate-card flex-fill">
							<div class="card-body">
								<div class="d-flex align-items-center justify-content-between">
									<div class="d-flex align-items-center">
										<span class="avatar avatar-lg bg-secondary rounded flex-shrink-0 me-2"><i class="ti ti-moneybag fs-24"></i></span>
										<div class="overflow-hidden">
											<h6 class="fw-semibold text-default">Lien</h6>
										</div>
									</div>
									<span class="btn btn-white secondary-btn-hover avatar avatar-sm p-0 flex-shrink-0 rounded-circle"><i class="ti ti-chevron-right fs-14"></i></span>
								</div>
							</div>
						</a>
					</div>
					<!-- /Links -->

				</div>
@endsection