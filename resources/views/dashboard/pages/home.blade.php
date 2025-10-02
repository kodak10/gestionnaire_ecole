@extends('dashboard.layouts.master')
@section('content')
	<!-- Page Header -->
				<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
					<div class="my-auto mb-2">
						<h3 class="page-title mb-1">Tableau de Bord {{ Auth::user()->getRoleNames()->first() ?? 'Aucun rôle' }}</h3>
						<nav>
							<ol class="breadcrumb mb-0">
								<li class="breadcrumb-item">
									<a href="#">Tableau de Bord</a>
								</li>
								<li class="breadcrumb-item active" aria-current="page">
									{{ Auth::user()->getRoleNames()->first() ?? 'Aucun rôle' }}
								</li>

							</ol>
						</nav>
					</div>
					@hasanyrole('SuperAdministrateur|Administrateur')
					<div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
						<div class="mb-2">
							<a href="{{ route('eleves.create') }}" class="btn btn-primary d-flex align-items-center me-3"><i class="ti ti-square-rounded-plus me-2"></i>Ajouter un Eleve</a>
						</div>
						<div class="mb-2">
							<a href="{{ route('reglements.index') }}" class="btn btn-light d-flex align-items-center">Faire un Règlement</a>
						</div>
					</div>
					@endhasanyrole
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
					@hasanyrole('SuperAdministrateur|Administrateur|Directeur')
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
									<div class="d-flex align-items-center justify-content-between border-top mt-3 pt-3">
										<p class="mb-0">Active : <span class="text-dark fw-semibold">254</span></p>
										<span class="text-light">|</span>
										<p>Inactive : <span class="text-dark fw-semibold">30</span></p>
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
									<div class="d-flex align-items-center justify-content-between border-top mt-3 pt-3">
										<p class="mb-0">Active : <span class="text-dark fw-semibold">254</span></p>
										<span class="text-light">|</span>
										<p>Inactive : <span class="text-dark fw-semibold">30</span></p>
									</div>
									
								</div>
							</div>
						</div>
					@endhasanyrole

					@hasanyrole('SuperAdministrateur|Administrateur|Directeur')
						<div class="col-xxl-3 col-sm-6 d-flex">
							<div class="card flex-fill animate-card border-0">
								<div class="card-body">
									<div class="d-flex align-items-center">
										<div class="avatar avatar-xl bg-danger-transparent me-2 p-1">
											<img src="assets/img/icons/student.svg" alt="img">
										</div>
										<div class="overflow-hidden flex-fill">
											<div class="d-flex align-items-center justify-content-between">
												<h2 class="counter">{{ $totalGarcons }}</h2>
												<span class="badge bg-danger">1.2%</span>
											</div>
											<p>Total Garcons</p>
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
												<h2 class="counter">{{ $totalFilles }}</h2>
												<span class="badge bg-skyblue">1.2%</span>
											</div>
											
											<p>Nombre de Filles</p>
										</div>
									</div>
									
									
								</div>
							</div>
						</div>
					@endhasanyrole
					@hasanyrole('SuperAdministrateur')
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
					@endhasanyrole

				</div>
				

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
				@hasanyrole('SuperAdministrateur|Administrateur|Caissiere')
					<div class="row">

						<!-- Total Earnings -->
						<div class="col-xxl-4 col-xl-6 d-flex flex-column">
							<div class="card flex-fill">
								<div class="card-body">
									<div class="d-flex align-items-center justify-content-between">
										<div>
											<h6 class="mb-1">Total Revenu 30 derniers jours</h6>
											<h2>$64,522,24</h2>
										</div>
										<span class="avatar avatar-lg bg-primary">
											<i class="ti ti-user-dollar"></i>
										</span>
									</div>
								</div>
								<div id="total-earning"></div>
							</div>
							<div class="card flex-fill">
								<div class="card-body">
									<div class="d-flex align-items-center justify-content-between">
										<div>
											<h6 class="mb-1">Total Dépenses 30 derniers jours</h6>
											<h2>$60,522,24</h2>
										</div>
										<span class="avatar avatar-lg bg-danger">
											<i class="ti ti-user-dollar"></i>
										</span>
									</div>
								</div>
								<div id="total-expenses"></div>
							</div>
						</div>
						<!-- /Total Earnings -->

						

						<!-- Fees Collection -->
						<div class="col-xxl-3 col-xl-6 order-2 order-xxl-3 d-flex flex-column">
							<div class="card flex-fill mb-2">
								<div class="card-body">
									<p class="mb-2">Total Fees Collected</p>
									<div class="d-flex align-items-end justify-content-between">
										<h4>$25,000,02</h4>
										<span class="badge badge-soft-success"><i class="ti ti-chart-line me-1"></i>1.2%</span>
									</div>
								</div>
							</div>
							<div class="card flex-fill mb-2">
								<div class="card-body">
									<p class="mb-2">Fine Collected till date</p>
									<div class="d-flex align-items-end justify-content-between">
										<h4>$4,56,64</h4>
										<span class="badge badge-soft-danger"><i class="ti ti-chart-line me-1"></i>1.2%</span>
									</div>
								</div>
							</div>
							<div class="card flex-fill mb-2">
								<div class="card-body">
									<p class="mb-2">Student Not Paid</p>
									<div class="d-flex align-items-end justify-content-between">
										<h4>$545</h4>
										<span class="badge badge-soft-info"><i class="ti ti-chart-line me-1"></i>1.2%</span>
									</div>
								</div>
							</div>
							<div class="card flex-fill mb-4">
								<div class="card-body">
									<p class="mb-2">Total Outstanding</p>
									<div class="d-flex align-items-end justify-content-between">
										<h4>$4,56,64</h4>
										<span class="badge badge-soft-danger"><i class="ti ti-chart-line me-1"></i>1.2%</span>
									</div>
								</div>
							</div>
						</div>
						<!-- /Fees Collection -->

					</div>
				@endhasanyrole
				@hasanyrole('SuperAdministrateur|Administrateur|Directeur|Enseignant')
					<div class="row">
						<div class= "col-lg-6">
							<div class="card flex-fill">
								<div class="card-header d-flex align-items-center justify-content-between flex-wrap">
									<h4 class="card-title">Meilleur Notes</h4>
									<div class="d-flex align-items-center">
										<div class="dropdown me-2 ">
											<a href="javascript:void(0);" class="bg-white dropdown-toggle" data-bs-toggle="dropdown"><i class="ti ti-calendar me-2"></i>All Classes
											</a>
											<ul class="dropdown-menu mt-2 p-3">
												<li>
													<a href="javascript:void(0);" class="dropdown-item rounded-1">
														I
													</a>
												</li>
												<li>
													<a href="javascript:void(0);" class="dropdown-item rounded-1">
														II
													</a>
												</li>
												<li>
													<a href="javascript:void(0);" class="dropdown-item rounded-1">
														III
													</a>
												</li>
											</ul>
										</div>
										
									</div>
								</div>
								<div class="card-body px-0">
									<div class="custom-datatable-filter table-responsive">
										<table class="table ">
											<thead class="thead-light">
												<tr>
													<th>Nom & Prénoms</th>
													<th>Discipline </th>
													<th>Discipline </th>
													<th>Discipline </th>
													<th>Discipline </th>
													<th>Discipline </th>
													<th>Moyenne Générale</th>
												</tr>
											</thead>
											<tbody>
												<tr>
													<td>
														<div class="d-flex align-items-center">
															<a href="student-details.html" class="avatar avatar-md"><img src="assets/img/students/student-01.jpg" class="img-fluid rounded-circle" alt="img"></a>
															<div class="ms-2">
																<p class="text-dark mb-0"><a href="student-details.html">Janet</a></p>
															</div>
														</div>
													</td>
													<td>10</td>
													<td>10</td>
													<td>10</td>
													<td>10</td>
													<td>10</td>
													<td>10</td>                                                
												</tr>

												<tr>
													<td>
														<div class="d-flex align-items-center">
															<a href="student-details.html" class="avatar avatar-md"><img src="assets/img/students/student-01.jpg" class="img-fluid rounded-circle" alt="img"></a>
															<div class="ms-2">
																<p class="text-dark mb-0"><a href="student-details.html">Janet</a></p>
															</div>
														</div>
													</td>
													<td>10</td>
													<td>10</td>
													<td>10</td>
													<td>10</td>
													<td>10</td>
													<td>10</td>                                                
												</tr>

												<tr>
													<td>
														<div class="d-flex align-items-center">
															<a href="student-details.html" class="avatar avatar-md"><img src="assets/img/students/student-01.jpg" class="img-fluid rounded-circle" alt="img"></a>
															<div class="ms-2">
																<p class="text-dark mb-0"><a href="student-details.html">Janet</a></p>
															</div>
														</div>
													</td>
													<td>10</td>
													<td>10</td>
													<td>10</td>
													<td>10</td>
													<td>10</td>
													<td>10</td>                                                
												</tr>

												<tr>
													<td>
														<div class="d-flex align-items-center">
															<a href="student-details.html" class="avatar avatar-md"><img src="assets/img/students/student-01.jpg" class="img-fluid rounded-circle" alt="img"></a>
															<div class="ms-2">
																<p class="text-dark mb-0"><a href="student-details.html">Janet</a></p>
															</div>
														</div>
													</td>
													<td>10</td>
													<td>10</td>
													<td>10</td>
													<td>10</td>
													<td>10</td>
													<td>10</td>                                                
												</tr>

												<tr>
													<td>
														<div class="d-flex align-items-center">
															<a href="student-details.html" class="avatar avatar-md"><img src="assets/img/students/student-01.jpg" class="img-fluid rounded-circle" alt="img"></a>
															<div class="ms-2">
																<p class="text-dark mb-0"><a href="student-details.html">Janet</a></p>
															</div>
														</div>
													</td>
													<td>10</td>
													<td>10</td>
													<td>10</td>
													<td>10</td>
													<td>10</td>
													<td>10</td>                                                
												</tr>

												<tr>
													<td>
														<div class="d-flex align-items-center">
															<a href="student-details.html" class="avatar avatar-md"><img src="assets/img/students/student-01.jpg" class="img-fluid rounded-circle" alt="img"></a>
															<div class="ms-2">
																<p class="text-dark mb-0"><a href="student-details.html">Janet</a></p>
															</div>
														</div>
													</td>
													<td>10</td>
													<td>10</td>
													<td>10</td>
													<td>10</td>
													<td>10</td>
													<td>10</td>                                                
												</tr>
												
											</tbody>
										</table>
									</div>
								</div>
							</div>
						</div>
						<div class="col-lg-6">
							<div class="row">
								<div class="col-sm-4 d-flex flex-column">
									<div class="bg-success-800 p-3 br-5 text-center flex-fill mb-4 pb-0  owl-height bg-01">
										<div class="owl-carousel student-slider h-100">
											<div class="item h-100">
												<div class="d-flex justify-content-between flex-column h-100">
													<div>
														<h5 class="mb-3 text-white">Best Performer</h5>
														<h4 class="mb-1 text-white">Rubell</h4>
														<p class="text-light">Physics Teacher</p>
													</div>
													<img src="assets/img/performer/performer-01.png" alt="img">
												</div>
											</div>
											<div class="item h-100">
												<div class="d-flex justify-content-between flex-column h-100">
													<div>
														<h5 class="mb-3 text-white">Best Performer</h5>
														<h4 class="mb-1 text-white">George Odell</h4>
														<p class="text-light">English Teacher</p>
													</div>
													<img src="assets/img/performer/performer-02.png" alt="img">
												</div>
											</div>
										</div>
									</div>
								</div>

								<div class="col-sm-4 d-flex flex-column">
									<div class="bg-info p-3 br-5 text-center flex-fill mb-4 pb-0 owl-height bg-02">
										<div class="owl-carousel teacher-slider h-100">
											<div class="item h-100">
												<div class="d-flex justify-content-between flex-column h-100">
													<div>
														<h5 class="mb-3 text-white">Star Students</h5>
														<h4 class="mb-1 text-white">Tenesa</h4>
														<p class="text-light">XII, A</p>
													</div>
													<img src="assets/img/performer/student-performer-01.png" alt="img">
												</div>
											</div>
											<div class="item h-100">
												<div class="d-flex justify-content-between flex-column h-100">
													<div>
														<h5 class="mb-3 text-white">Star Students</h5>
														<h4 class="mb-1 text-white">Michael </h4>
														<p>XII, B</p>
													</div>
													<img src="assets/img/performer/student-performer-02.png" alt="img">
												</div>
											</div>
										</div>
									</div>
								</div>

								<div class="col-sm-4 d-flex flex-column">
									<div class="bg-success-800 p-3 br-5 text-center flex-fill mb-4 pb-0  owl-height bg-01">
										<div class="owl-carousel student-slider h-100">
											<div class="item h-100">
												<div class="d-flex justify-content-between flex-column h-100">
													<div>
														<h5 class="mb-3 text-white">Best Performer</h5>
														<h4 class="mb-1 text-white">Rubell</h4>
														<p class="text-light">Physics Teacher</p>
													</div>
													<img src="assets/img/performer/performer-01.png" alt="img">
												</div>
											</div>
											<div class="item h-100">
												<div class="d-flex justify-content-between flex-column h-100">
													<div>
														<h5 class="mb-3 text-white">Best Performer</h5>
														<h4 class="mb-1 text-white">George Odell</h4>
														<p class="text-light">English Teacher</p>
													</div>
													<img src="assets/img/performer/performer-02.png" alt="img">
												</div>
											</div>
										</div>
									</div>
								</div>

								<div class="col-sm-4 d-flex flex-column">
									<div class="bg-info p-3 br-5 text-center flex-fill mb-4 pb-0 owl-height bg-02">
										<div class="owl-carousel teacher-slider h-100">
											<div class="item h-100">
												<div class="d-flex justify-content-between flex-column h-100">
													<div>
														<h5 class="mb-3 text-white">Star Students</h5>
														<h4 class="mb-1 text-white">Tenesa</h4>
														<p class="text-light">XII, A</p>
													</div>
													<img src="assets/img/performer/student-performer-01.png" alt="img">
												</div>
											</div>
											<div class="item h-100">
												<div class="d-flex justify-content-between flex-column h-100">
													<div>
														<h5 class="mb-3 text-white">Star Students</h5>
														<h4 class="mb-1 text-white">Michael </h4>
														<p>XII, B</p>
													</div>
													<img src="assets/img/performer/student-performer-02.png" alt="img">
												</div>
											</div>
										</div>
									</div>
								</div>
								<div class="col-sm-4 d-flex flex-column">
									<div class="bg-success-800 p-3 br-5 text-center flex-fill mb-4 pb-0  owl-height bg-01">
										<div class="owl-carousel student-slider h-100">
											<div class="item h-100">
												<div class="d-flex justify-content-between flex-column h-100">
													<div>
														<h5 class="mb-3 text-white">Best Performer</h5>
														<h4 class="mb-1 text-white">Rubell</h4>
														<p class="text-light">Physics Teacher</p>
													</div>
													<img src="assets/img/performer/performer-01.png" alt="img">
												</div>
											</div>
											<div class="item h-100">
												<div class="d-flex justify-content-between flex-column h-100">
													<div>
														<h5 class="mb-3 text-white">Best Performer</h5>
														<h4 class="mb-1 text-white">George Odell</h4>
														<p class="text-light">English Teacher</p>
													</div>
													<img src="assets/img/performer/performer-02.png" alt="img">
												</div>
											</div>
										</div>
									</div>
								</div>

								<div class="col-sm-4 d-flex flex-column">
									<div class="bg-info p-3 br-5 text-center flex-fill mb-4 pb-0 owl-height bg-02">
										<div class="owl-carousel teacher-slider h-100">
											<div class="item h-100">
												<div class="d-flex justify-content-between flex-column h-100">
													<div>
														<h5 class="mb-3 text-white">Star Students</h5>
														<h4 class="mb-1 text-white">Tenesa</h4>
														<p class="text-light">XII, A</p>
													</div>
													<img src="assets/img/performer/student-performer-01.png" alt="img">
												</div>
											</div>
											<div class="item h-100">
												<div class="d-flex justify-content-between flex-column h-100">
													<div>
														<h5 class="mb-3 text-white">Star Students</h5>
														<h4 class="mb-1 text-white">Michael </h4>
														<p>XII, B</p>
													</div>
													<img src="assets/img/performer/student-performer-02.png" alt="img">
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				@endhasanyrole
				
				
@endsection