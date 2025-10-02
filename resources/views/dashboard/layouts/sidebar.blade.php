<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-inner slimscroll">
        <div id="sidebar-menu" class="sidebar-menu" style="min-height:100vh">
            @php
                $user = Auth::user();
                $ecole = $user ? $user->ecole : null;
                $currentRoute = Route::currentRouteName();
            @endphp
            <ul>
                <li>
                    <a href="javascript:void(0);" class="d-flex align-items-center border bg-white rounded p-2 mb-4">
                        <img src="{{ $ecole && $ecole->logo ? asset($ecole->logo) : 'assets/img/icons/global-img.svg' }}" 
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
                            <a href="javascript:void(0);" class="{{ in_array($currentRoute, ['dashboard']) ? 'active' : '' }}">
                                <i class="ti ti-home"></i><span>Dashboard</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{ route('dashboard') }}" class="{{ $currentRoute == 'dashboard' ? 'active' : '' }}">Tableau de Bord</a></li>
                            </ul>
                        </li>
                    </ul>
                </li>
                
                <li>
                    <h6 class="submenu-hdr"><span>GESTIONS</span></h6>
                    <ul>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ in_array($currentRoute, ['eleves.create', 'preinscriptions.index', 'reinscriptions.index', 'eleves.index', 'notes.index']) ? 'active' : '' }}">
                                <i class="ti ti-users"></i><span>Eleves</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                @hasanyrole('SuperAdministrateur|Administrateur')
                                <li><a href="{{ route('eleves.create') }}" class="{{ $currentRoute == 'eleves.create' ? 'active' : '' }}">Inscription</a></li>
                                @endhasanyrole
                                @hasanyrole('SuperAdministrateur|Administrateur')
                                <li><a href="{{ route('preinscriptions.index') }}" class="{{ $currentRoute == 'preinscriptions.index' ? 'active' : '' }}">PréInscription</a></li>
                                @endhasanyrole
                                @hasanyrole('SuperAdministrateur|Administrateur')
                                <li><a href="{{ route('reinscriptions.index') }}" class="{{ $currentRoute == 'reinscriptions.index' ? 'active' : '' }}">RéInscription</a></li>
                                @endhasanyrole
                                <li><a href="{{ route('eleves.index') }}" class="{{ $currentRoute == 'eleves.index' ? 'active' : '' }}">Liste des Eleves</a></li>
                                @hasanyrole('SuperAdministrateur|Administrateur|Directeur|Enseignant')
                                <li><a href="{{ route('notes.index') }}" class="{{ $currentRoute == 'notes.index' ? 'active' : '' }}">Saisie de Note</a></li>
                                @endhasanyrole
                            </ul>
                        </li>
                        
                        @hasanyrole('SuperAdministrateur|Administrateur|Caissiere')
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ in_array($currentRoute, ['scolarite.index', 'reglements.index']) ? 'active' : '' }}">
                                <i class="ti ti-wallet"></i><span>Scolarités</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                @hasanyrole('SuperAdministrateur|Administrateur')
                                <li><a href="{{ route('scolarite.index') }}" class="{{ $currentRoute == 'scolarite.index' ? 'active' : '' }}">Scolarités</a></li>
                                @endhasanyrole
                                <li><a href="{{ route('reglements.index') }}" class="{{ $currentRoute == 'reglements.index' ? 'active' : '' }}">Règlements</a></li>
                            </ul>
                        </li>
                        @endhasanyrole
                        
                        @hasanyrole('SuperAdministrateur|Administrateur|Caissiere')
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ in_array($currentRoute, ['cantine.index']) ? 'active' : '' }}">
                                <i class="ti ti-coffee"></i><span>Cantine</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{ route('cantine.index') }}" class="{{ $currentRoute == 'cantine.index' ? 'active' : '' }}">Règlements</a></li>
                            </ul>
                        </li>
                        @endhasanyrole
                        
                        @hasanyrole('SuperAdministrateur|Administrateur|Caissiere')
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ in_array($currentRoute, ['transport.index']) ? 'active' : '' }}">
                                <i class="ti ti-truck"></i><span>Transport</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{ route('transport.index') }}" class="{{ $currentRoute == 'transport.index' ? 'active' : '' }}">Règlements</a></li>
                            </ul>
                        </li>
                        @endhasanyrole
                        
                        @hasanyrole('SuperAdministrateur|Administrateur')
                        <li><a href="{{ route('relance.index') }}" class="{{ $currentRoute == 'relance.index' ? 'active' : '' }}"><i class="ti ti-bell"></i><span>Relances</span></a></li>
                        @endhasanyrole
                        
                        @hasanyrole('SuperAdministrateur|Administrateur|Caissiere')
                        <li><a href="{{ route('depenses.index') }}" class="{{ $currentRoute == 'depenses.index' ? 'active' : '' }}"><i class="ti ti-credit-card"></i><span>Depenses</span></a></li>
                        @endhasanyrole
                        
                        @hasanyrole('SuperAdministrateur|Administrateur|Directeur')
                        <li><a href="#" class="{{ $currentRoute == 'messages' ? 'active' : '' }}"><i class="ti ti-mail"></i><span>Messages</span></a></li>
                        @endhasanyrole
                    </ul>
                </li>

                @hasanyrole('SuperAdministrateur|Administrateur|Directeur|Caissiere')
                <li>
                    <h6 class="submenu-hdr"><span>Consultation</span></h6>
                    <ul>
                        @hasanyrole('SuperAdministrateur|Administrateur|Caissiere')
                        <li><a href="{{ route('journal-paiements.index') }}" class="{{ $currentRoute == 'journal-paiements.index' ? 'active' : '' }}"><i class="ti ti-file-text"></i><span>Journal de Caisse</span></a></li>
                        @endhasanyrole
                        
                        @hasanyrole('SuperAdministrateur|Administrateur')
                        <li><a href="#" class="{{ $currentRoute == 'bilan-financier' ? 'active' : '' }}"><i class="ti ti-bar-chart"></i><span>Bilan Financier</span></a></li>
                        @endhasanyrole
                        
                        @hasanyrole('SuperAdministrateur|Administrateur|Directeur')
                        <li><a href="#" class="{{ $currentRoute == 'archivages' ? 'active' : '' }}"><i class="ti ti-archive"></i><span>Archivages</span></a></li>
                        @endhasanyrole
                    </ul>
                </li>
                @endhasanyrole

                <li>
                    <h6 class="submenu-hdr"><span>Paramètrages</span></h6>
                    <ul>
                        @hasanyrole('SuperAdministrateur')
                        <li><a href="{{ route('ecoles.index') }}" class="{{ $currentRoute == 'ecoles.index' ? 'active' : '' }}"><i class="ti ti-building"></i><span>Etablissement</span></a></li>
                        @endhasanyrole
                        
                        @hasanyrole('SuperAdministrateur|Administrateur|Directeur')
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ in_array($currentRoute, ['classes.index', 'matieres.index', 'mentions.index']) ? 'active' : '' }}">
                                <i class="ti ti-book"></i><span>Scolaires</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{ route('classes.index') }}" class="{{ $currentRoute == 'classes.index' ? 'active' : '' }}">Classes</a></li>
                                <li><a href="{{ route('matieres.index') }}" class="{{ $currentRoute == 'matieres.index' ? 'active' : '' }}">Disciplines</a></li>
                                <li><a href="{{ route('mentions.index') }}" class="{{ $currentRoute == 'mentions.index' ? 'active' : '' }}">Critères de Notation</a></li>
                            </ul>
                        </li>
                        @endhasanyrole

                        @hasanyrole('SuperAdministrateur')
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ in_array($currentRoute, ['tarifs.index', 'tarifs-mensuels.index']) ? 'active' : '' }}">
                                <i class="ti ti-cash"></i><span>Scolarités</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{ route('tarifs.index') }}" class="{{ $currentRoute == 'tarifs.index' ? 'active' : '' }}">Frais de Scolarité</a></li>
                                <li><a href="{{ route('tarifs-mensuels.index') }}" class="{{ $currentRoute == 'tarifs-mensuels.index' ? 'active' : '' }}">Parametrage de Règlement</a></li>
                            </ul>
                        </li>
                        @endhasanyrole
                        
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ in_array($currentRoute, ['profile', 'users.index']) ? 'active' : '' }}">
                                <i class="ti ti-user"></i><span>Compte</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li>
                                    <a href="{{ route('profile') }}" class="{{ $currentRoute == 'profile' ? 'active' : '' }}">Mon Profil</a>
                                </li>
                                @hasanyrole('SuperAdministrateur')
                                <li><a href="{{ route('users.index') }}" class="{{ $currentRoute == 'users.index' ? 'active' : '' }}">Utilisateurs</a></li>
                                @endhasanyrole
                            </ul>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div>
<!-- /Sidebar -->
