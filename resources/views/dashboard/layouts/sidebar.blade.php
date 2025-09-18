<!-- Sidebar -->
		<div class="sidebar" id="sidebar">
			<div class="sidebar-inner slimscroll">
				<div id="sidebar-menu" class="sidebar-menu">
					@php
						$user = Auth::user();
						$ecole = $user && method_exists($user, 'ecole') ? $user->ecole : null;
					@endphp

					<ul>
						<li>
							<a href="javascript:void(0);" class="d-flex align-items-center border bg-white rounded p-2 mb-4">
								<img src="{{ $ecole && $ecole->logo ? asset('storage/' . $ecole->logo) : 'assets/img/icons/global-img.svg' }}" 
									class="avatar avatar-md img-fluid rounded" 
									alt="Logo {{ $ecole->nom ?? 'Ecole' }}">
								<span class="text-dark ms-2 fw-normal">{{ $ecole->nom ?? 'Ecole non définie' }}</span>
							</a>
						</li>
					</ul>


					<ul>
						<li>
							<h6 class="submenu-hdr"><span>Main</span></h6>
							<ul>
								<li class="submenu">
									<a href="javascript:void(0);" class="subdrop active"><i class="ti ti-layout-dashboard"></i><span>Dashboard</span><span class="menu-arrow"></span></a>
									<ul>
										<li><a href="{{ route('dashboard') }}" class="active">Tableau de Bord Admin</a></li>
										<li><a href="#">Tableau de Bord Directeur</a></li>
										<li><a href="#">Tableau de Bord Caissière</a></li>
									</ul>
								</li>
								
							</ul>
						</li>
						
						<li>
							<h6 class="submenu-hdr"><span>GESTIONS</span></h6>
							<ul>
								<li class="submenu">
									<a href="javascript:void(0);"><i class="ti ti-school"></i><span>Eleves</span><span class="menu-arrow"></span></a>
									<ul>
                                        <li><a href="{{ route('eleves.create') }}">Inscription</a></li>
										<li><a href="{{ route('preinscriptions.index') }}">PréInscription</a></li>
                                        <li><a href="{{ route('reinscriptions.index') }}">RéInscription</a></li>
										<li><a href="{{ route('eleves.index') }}">Liste des Eleves</a></li>
										<li><a href="{{ route('notes.index') }}">Saisie de Note</a></li>
                                    </ul>
								</li>
								<li class="submenu">
									<a href="javascript:void(0);"><i class="ti ti-school"></i><span>Scolarités</span><span class="menu-arrow"></span></a>
									<ul>
                                        <li><a href="{{ route('scolarite.index') }}">Scolarités</a></li>
										<li><a href="{{ route('reglements.index') }}">Règlements</a></li>
										
										
									</ul>
								</li>
                                <li class="submenu">
									<a href="javascript:void(0);"><i class="ti ti-school"></i><span>Cantine</span><span class="menu-arrow"></span></a>
									<ul>
                                        <li><a href="{{ route('cantine.index') }}">Règlements</a></li>
									</ul>
								</li>
								<li class="submenu">
									<a href="javascript:void(0);"><i class="ti ti-school"></i><span>Transport</span><span class="menu-arrow"></span></a>
									<ul>
                                        <li><a href="{{ route('transport.index') }}">Règlements</a></li>
									</ul>
								</li>
								<li><a href="{{ route('relance.index') }}"><i class="ti ti-calendar-due"></i><span>Relances</span></a></li>
								<li><a href="{{ route('depenses.index') }}"><i class="ti ti-calendar-due"></i><span>Depenses</span></a></li>

								<li><a href="#"><i class="ti ti-calendar-due"></i><span>Messages</span></a></li>

							</ul>

						</li>
						
						<li>
							<h6 class="submenu-hdr"><span>Consultation</span></h6>
							<ul>
								<li><a href="{{ route('journal-paiements.index') }}"><i class="ti ti-calendar-due"></i><span>Journal de Caisse</span></a></li>
								<li><a href="#"><i class="ti ti-calendar-due"></i><span>Bilan Financier</span></a></li>
								<li><a href="#"><i class="ti ti-calendar-due"></i><span>Archivages</span></a></li>
                            </ul>
						</li>
						<li>
							<h6 class="submenu-hdr"><span>Paramètrages</span></h6>
							<ul>
                                <li><a href="{{ route('ecoles.index') }}"><i class="ti ti-brand-nuxt"></i><span>Etablissement</span></a></li>
                                {{-- <li><a href="#"><i class="ti ti-brand-nuxt"></i><span>Messageries</span></a></li> --}}
								<li class="submenu">
									<a href="javascript:void(0);">
										<i class="ti ti-shield-cog"></i><span>Scolaires</span><span class="menu-arrow"></span>
									</a>
									<ul>
										<li><a href="{{ route('classes.index') }}">Classes</a></li>
                                        <li><a href="{{ route('matieres.index') }}">Disciplines</a></li>
										<li><a href="{{ route('mentions.index') }}">Critères de Notation</a></li>
									</ul>
								</li>
                                <li class="submenu">
									<a href="javascript:void(0);">
										<i class="ti ti-shield-cog"></i><span>Scolarités</span><span class="menu-arrow"></span>
									</a>
									<ul>
										
										<li><a href="{{ route('tarifs.index') }}">Frais de Scolarité</a></li>
                                        <li><a href="{{ route('tarifs-mensuels.index') }}">Parametrage de Règlement</a></li>
									</ul>
								</li>
                                <li class="submenu">
									<a href="javascript:void(0);">
										<i class="ti ti-shield-cog"></i><span>Compte</span><span class="menu-arrow"></span>
									</a>
									<ul>
										<li><a href="#">Mon Compte</a></li>
										<li><a href="#">Utilisateurs</a></li>
										<li><a href="#">Roles & Permissions</a></li>
									</ul>
								</li>
								
							</ul>
						</li>

						
					</ul>
				</div>
			</div>
		</div>
		<!-- /Sidebar -->