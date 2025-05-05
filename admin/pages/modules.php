<?php
defined('EVO') or die('Que fais-tu là?');

has_permission('admin.manage_modules', true);

$modules = [];

if ($plugin_name = App::POST('activate_plugin')) {
    try {
        if (App::activateModule($plugin_name)) {
            App::setSuccess("Module <strong>$plugin_name</strong> activé!");
        }
    } catch (Exception $e) {
        App::setWarning("Impossible d'activer <strong>$plugin_name</strong>!", true);
        App::setWarning('<pre>' . html_encode($e->getMessage()) . '</pre>', true);
    }
}

if ($plugin_name = App::POST('deactivate_plugin')) {
    try {
        if (App::deactivateModule($plugin_name)) {
            App::setSuccess("Module <strong>$plugin_name</strong> désactivé!");
        }
    } catch (Exception $e) {
        App::setNotice("Le module <strong>$plugin_name</strong> a été désactivé cependant il a produit une erreur:", true);
        App::setNotice('<pre>' . html_encode($e->getMessage()) . '</pre>', true);
    }
}

if ($plugin_name = App::POST('delete_plugin')) {
    if (App::deleteModule($plugin_name)) {
        App::setSuccess("Module <strong>$plugin_name</strong> supprimé!");
    }
}

// Importation de plugin depuis un fichier ZIP
if (isset($_FILES['plugin_file']) && is_uploaded_file($_FILES['plugin_file']['tmp_name'])) {
    $zip = new ZipArchive;
    if ($zip->open($_FILES['plugin_file']['tmp_name']) === true) {
        $tmpdir = sys_get_temp_dir() . '/' . random_hash(8);
        $zip->extractTo($tmpdir);
        $zip->close();

        $manifest = glob($tmpdir . '/{module.json,*/module.json}', GLOB_BRACE)[0] ?? null;

        if ($manifest && $module = Evo\EvoInfo::fromFile($manifest)) {
            $target = ROOT_DIR . '/modules/' . $module->name;
            $source = dirname($manifest);

            if (!file_exists($target) && rename($source, $target)) {
                App::setSuccess('Module importé. Vous pouvez maintenant l\'activer.');
            } else {
                App::setWarning('Le module existe déjà ou une erreur est survenue.');
            }
        } else {
            App::setWarning('Ce module est invalide, veuillez consulter la documentation ou l\'importer manuellement via FTP.');
        }

        rrmdir($tmpdir);
    } else {
        App::setWarning('Fichier ZIP invalide!');
    }
}

// Gestion des mises à jour des modules
$updates = &$_SESSION['updates'];

foreach (glob(ROOT_DIR . '/modules/*/module.json', GLOB_BRACE) as $filename) {
    if ($module = \Evo\EvoInfo::fromFile($filename)) {
        $key = basename(dirname($filename));
        $modules[$key] = $module;

        if (!isset($updates[$key]['checked']) || $updates[$key]['checked'] < time() - 300) {
            $update = $module->checkForUpdates();
            $updates[$key] = [
                'checked' => time(),
                'content' => $update ? "<a href=\"" . html_encode($update->download ?: $update->homepage) . "\">Nouvelle version: " . html_encode($update->version) . "</a>" : ''
            ];
        }
    }
}

// Sauvegarde des paramètres du module actuel
$current_plugin = App::getModule(App::GET('plugin', ''));

if (IS_POST && $current_plugin && $current_plugin->settings) {
    if (settings_save($current_plugin->settings, App::POST())) {
        App::setSuccess('Configuration mise à jour!');
    }
}
?>
<style>

table td {
	line-height: 28px;
	color: #6c757d!important;
}

table th {
	font-weight: 400;
}

.plugin_header {
    min-height: 250px;
}

.plugin_header .header {
    position: relative;
    top: 60px;
}

.plugin_header .header .title{
    float: left
}

.btn {
	-webkit-box-shadow: none;
    box-shadow: none;
}

.nav-pills .nav-link.active, .nav-pills .show>.nav-link {
    color: #fff;
    background-color: #273339;
}

.bg-grad-evo {
    background: #263238;  /* fallback for old browsers */
    background: -webkit-linear-gradient(to left, #37474f, #263238);  /* Chrome 10-25, Safari 5.1-6 */
    background: linear-gradient(to left, #37474f, #263238); /* W3C, IE 10+/ Edge, Firefox 16+, Chrome 26+, Opera 12+, Safari 7+ */ 
}

.en-container {
	padding: 0;
}

.icon-background {
	color: #d1d6e8;
    height: 100%;
    width: 100%;
    z-index: 0;
    line-height: 120px;
    position: absolute;
    left: 0;
    top: 0;
    opacity: 0.03;
    text-align: right;
    font-size: 300px;
}
</style>

<?php if($current_plugin) { ?>
	<div class="plugin_header bg-grad-evo">
		<div class="container header">
			<div class="d-flex bd-highlight w-100">
				<div class="p-2 w-100 bd-highlight">
					<h3 class="text-white">Modules Management</h3>
					<span class="text-muted lead"><?= $current_plugin->infos->name .' v'. $current_plugin->infos->version ?></span><br/>
				</div>
				<div class="p-2 flex-shrink-1 bd-highlight">
					<span class="icon-background fas fa-tools"></span>
				</div>
			</div>
		</div>
	</div>
	<div class="card card-body" style="margin: 24px 40px 40px 40px;margin-top: -70px;"><?= settings_form($current_plugin->settings) ?></div>
<?php return; }  ?>

<?php
    $list = file_get_contents("https://dev.evolution-network.ca/plugin_checker.json");
	$data = json_decode($list);
    $gui = $data->Themes;
	$mod = $data->Modules;
	$lang = $data->Langues;
?>

<form method="post">
	<div class="plugin_header bg-grad-evo">
		<div class="container header">
			<div class="d-flex bd-highlight w-100">
				<div class="p-2 w-100 bd-highlight">
					<h3 class="text-white">Modules Management</h3>
					<?php if($current_plugin){ echo $current_plugin->nom; } ?>
					<span class="text-muted small"><?= __('ticket_system/tss_header.version'); ?> : 1.0.0 alpha</span><br/>
				</div>
				<div class="p-2 flex-shrink-1 bd-highlight">
					<span class="icon-background fas fa-tools"></span>
				</div>
			</div>
		</div>
	</div>
	<div class="card" style="margin: 24px 40px 40px 40px;margin-top: -70px;">
		<div class="card-header">
			<ul class="nav nav-tabs card-header-tabs" id="tab" role="tablist">
			<li class="nav-item" role="tab"><a class="nav-link active" id="installed-tab" data-toggle="tab" href="#installed" role="tab" aria-controls="installed" aria-selected="true">Composants installés</a></li>
			<li class="nav-item" role="tab"><a class="nav-link" id="themes-tab" data-toggle="tab" href="#themes" role="tab" aria-controls="themes" aria-selected="false">Thèmes graphiques</a></li>
			<li class="nav-item" role="tab"><a class="nav-link" id="modules-tab" data-toggle="tab" href="#modules" role="tab" aria-controls="modules" aria-selected="false">Modules</a></li>
			<li class="nav-item" role="tab"><a class="nav-link" id="lang-tab" data-toggle="tab" href="#lang" role="tab" aria-controls="lang" aria-selected="false">Langues</a></li>
			<li class="nav-item" role="tab"><a class="nav-link" id="import-tab" data-toggle="tab" href="#import" role="tab" aria-controls="import" aria-selected="false">Importer</a></li>
			<li class="nav-item" role="tab"><a class="nav-link" id="settings-tab" data-toggle="tab" href="#settings" role="tab" aria-controls="settings" aria-selected="false">Paramètres</a></li>
			</ul>
		</div>
		<div class="card-body tab-content" id="TabContent">
			<div class="tab-pane fade show active" id="installed" role="tabpanel" aria-labelledby="installed-tab">
				<div class="card-body">
					<h5 class="card-title">Thèmes</h5>
					<p class="card-text">
						<table class="table table-borderless table-sm table-responsive-lg small">
							<thead class="table-dark">
								<th>Nom</th>
								<th>Description</th>
								<th>Auteur</th>
								<th>Version installé</th>
								<th>Version disponible</th>
								<th class="center">Action</th>
							</thead>
							<tbody>
								<?php
									foreach($modules as $plugin_id => $module) {
										if($module->exports[0] == "theme") { 
											echo "<tr>
											<td><a href='". html_encode($module->homepage) ."' target='_blank'>". html_encode($module->name) ."</a></td>
											<td>" . html_encode($module->description) . "</td>
											<td>" . implode("\n", html_encode($module->authors)) . "</td>
											<td>1.3.x</td>
											<td>" . html_encode($module->version) . "</td>
											<td class='right'>";
											if (App::getModule($plugin_id)) {
												if ($module->settings) {
													echo '<a href="?page=modules&plugin='.$plugin_id.'" class="btn btn-sm btn-outline-primary">Paramètres</a> ';
												}
												echo '<button type="submit" name="deactivate_plugin" class="btn btn-sm btn-outline-warning" value="'.$plugin_id.'">Désactiver</button> ';
											} else {
												echo '<button type="submit" name="activate_plugin" class="btn btn-sm btn-outline-success" value="'.$plugin_id.'">Activer</button> ';
												echo '<button type="submit" name="delete_plugin" class="btn btn-sm btn-outline-danger" value="'.$plugin_id.'" onclick="return confirm(\'Le module et tous ses fichiers seront supprimés. Continuer?\');">Supprimer</button> ';
											}
										echo "</td>
										</tr>";
										}
									}
								?>
							</tbody>
						</table>
					</p>
				</div>
				<div class="card-body">
					<h5 class="card-title">Modules</h5>
					<p class="card-text">
						<table class="table table-borderless table-sm table-responsive-lg small">
							<thead class="table-dark">
								<th>Nom</th>
								<th>Description</th>
								<th>Auteur</th>
								<th>Version installé</th>
								<th>Version disponible</th>
								<th class="center">Action</th>
							</thead>
							<tbody>
								<?php
									foreach($modules as $plugin_id => $module) {
										if($module->exports[0] == "plugin") { 
											echo "<tr>
											<td><a href='". html_encode($module->homepage) ."' target='_blank'>". html_encode($module->name) ."</a></td>
											<td>" . html_encode($module->description) . "</td>
											<td>" . implode("\n", html_encode($module->authors)) . "</td>
											<td>1.3.x</td>
											<td>" . html_encode($module->version) . "</td>
											<td class='right'>";
											if (App::getModule($plugin_id)) {
												if ($module->settings) {
													echo '<a href="?page=modules&plugin='.$plugin_id.'" class="btn btn-sm btn-outline-primary">Paramètres</a> ';
												}
												echo '<button type="submit" name="deactivate_plugin" class="btn btn-sm btn-outline-warning" value="'.$plugin_id.'">Désactiver</button> ';
											} else {
												echo '<button type="submit" name="activate_plugin" class="btn btn-sm btn-outline-success" value="'.$plugin_id.'">Activer</button> ';
												echo '<button type="submit" name="delete_plugin" class="btn btn-sm btn-outline-danger" value="'.$plugin_id.'" onclick="return confirm(\'Le module et tous ses fichiers seront supprimés. Continuer?\');">Supprimer</button> ';
											}
										echo "</td>
										</tr>";
										}
									}
								?>
							</tbody>
						</table>
					</p>
				</div>
			</div>
			<div class="tab-pane fade" id="themes" role="tabpanel" aria-labelledby="themes-tab">
				<table class="table table-borderless table-sm table-responsive-lg small">
					<thead class="table-dark">
						<th>Nom</th>
						<th>Description</th>
						<th>Auteur</th>
						<th>CMS Version</th>
						<th>Plugin Version</th>
						<th></th>
					</thead>
					<tbody class="table-hover">
						<?php foreach ($gui as $key => $value) : ?>						
							<tr>
								<td class="text-muted"><?= $value->name ?></td>
								<td class="text-muted"><?= $value->description ?></td>
								<td class="text-muted"><?= $value->author ?></td>
								<td class="text-muted"><?= $value->cms_version ?></td>
								<td class="text-muted"><?= $value->plugin_version ?></td>
								<td style="text-align: right">
									<?php if($value->download) : ?><a href="<?= $value->download ?>" target="_blank" class="btn btn-sm"><i class="fas fa-lg fa-download"></i> Télécharger</a> <?php endif; ?>
									<?php if($value->download) : ?><a href="#" target="_blank" class="btn btn-sm"><i class="fas fa-lg fa-microchip"></i> Installer</a> <?php endif; ?>
									<?php if($value->preview) : ?><a href="<?= $value->preview ?>" target="_blank" class="btn btn-sm"><i class="far fa-lg fa-images"></i> Aperçu</a> <?php endif; ?>
									<?php if($value->website) : ?><a href="<?= $value->website ?>" target="_blank" class="btn btn-sm"><i class="fas fa-lg fa-globe-americas"></i> Site Web</a> <?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<div class="tab-pane fade" id="modules" role="tabpanel" aria-labelledby="modules-tab">
				<table class="table table-borderless table-sm table-responsive-lg small">
					<thead class="table-dark">
						<th>Nom</th>
						<th>Description</th>
						<th>Auteur</th>
						<th>CMS Version</th>
						<th>Plugin Version</th>
						<th></th>
					</thead>
					<tbody class="table-hover">
						<?php foreach ($mod as $key => $value) : ?>						
							<tr>
								<td class="text-muted"><?= $value->name ?></td>
								<td class="text-muted"><?= $value->description ?></td>
								<td class="text-muted"><?= $value->author ?></td>
								<td class="text-muted"><?= $value->cms_version ?></td>
								<td class="text-muted"><?= $value->plugin_version ?></td>
								<td style="text-align: right">
									<?php if($value->download) : ?><a href="<?= $value->download ?>" target="_blank" class="btn btn-sm"><i class="fas fa-lg fa-download"></i> Télécharger</a> <?php endif; ?>
									<?php if($value->download) : ?><a href="#" target="_blank" class="btn btn-sm"><i class="fas fa-lg fa-microchip"></i> Installer</a> <?php endif; ?>
									<?php if($value->preview) : ?><a href="<?= $value->preview ?>" target="_blank" class="btn btn-sm"><i class="far fa-lg fa-images"></i> Aperçu</a> <?php endif; ?>
									<?php if($value->website) : ?><a href="<?= $value->website ?>" target="_blank" class="btn btn-sm"><i class="fas fa-lg fa-globe-americas"></i> Site Web</a> <?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<div class="tab-pane fade" id="lang" role="tabpanel" aria-labelledby="lang-tab">
				<table class="table table-borderless table-sm table-responsive-lg small">
					<thead class="table-dark">
						<th>Nom</th>
						<th>Auteur</th>
						<th>Avancement</th>
						<th>CMS Version</th>
						<th></th>
					</thead>
					<tbody class="table-hover">
						<?php foreach ($lang as $key => $value) : ?>						
							<tr>
								<td class="text-muted"><img src="<?= App::getAsset('/img/flags/'.strtolower($value->flag).'.png'); ?>" style="height:15px;" title="<?= @COUNTRIES[$value->flag] ?>"/> <?= $value->name ?></td>
								<td class="text-muted"><?= $value->author ?></td>
								<td>
									<div class="progress" style="margin-top: 5px;">
										<div class="progress-bar progress-bar-striped <?php if($value->progress === '100'){ echo 'bg-success'; }else{ echo 'bg-warning'; } ?> progress-bar-animated" role="progressbar" aria-valuenow="<?= $value->progress ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?= $value->progress ?>%"></div>
									</div>
								</td>
								<td class="text-muted"><?= $value->cms_version ?></td>
								<td style="text-align: right">
									<?php if($value->download) : ?><a href="<?= $value->download ?>" target="_blank" class="btn btn-sm"><i class="fas fa-lg fa-download"></i> Télécharger</a> <?php endif; ?>
									<?php if($value->download) : ?><a href="#" target="_blank" class="btn btn-sm"><i class="fas fa-lg fa-microchip"></i> Installer</a> <?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<div class="tab-pane fade" id="import" role="tabpanel" aria-labelledby="import-tab">
				<?php if (!$current_plugin && class_exists('ZipArchive')) { ?>
					<div class="float-right">
						<form method="post" class="form-horizontal" enctype="multipart/form-data">
								Installer un module: <input type="file" name="plugin_file" style="display: inline;width:200px;"><button type="submit">Upload</button>
						</form>
					</div>
				<?php } ?>
			</div>
			<div class="tab-pane fade" id="settings" role="tabpanel" aria-labelledby="settings-tab">...</div>
		</div>
	</div>
</form>
