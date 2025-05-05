<?php defined('EVO') or die('Que fais-tu là?');

has_permission('admin.files', true);
?>
<style>
	#admin-page { padding: 0px !important; }
	#footer { display:none; }
</style>
<script type="text/javascript" src="<?= App::getAsset('/includes/Editors/ace/ace.js') ?>"></script>
<?php
    function is_editable($file) {
        $file = basename($file);
        $allowed_exts = ['txt', 'php', 'htm', 'html', 'js', 'json', 'css', 'tpl', 'xml', 'md', 'htaccess', 'htpasswd', 'conf', 'ini'];
        $extension = pathinfo($file, PATHINFO_EXTENSION);

        return in_array($extension, $allowed_exts) || strpos($file, '.') === false;
    }

    function files_tree($dir, $current = '', $dot_files = true) {
        $data = '<ul class="collapsible">';
        $files = glob($dir . DIRECTORY_SEPARATOR . ($dot_files ? '{,.??}*' : '*'), GLOB_BRACE);

        if (!$files) {
            return '<ul class="collapsible"><li>Aucun fichier trouvé</li></ul>';
        }

        $_dirs = $_files = [];

        foreach ($files as $path) {
            if (is_dir($path)) {
                $_dirs[] = $path;
            } else {
                $_files[] = $path;
            }
        }

        asort($_dirs);
        asort($_files);
        $files = array_merge($_dirs, $_files);

        foreach ($files as $path) {
            $file = basename($path);
            $selected = (!empty($current) && strpos($current, $path) === 0);

            if (is_dir($path)) {
                $dir_id = preg_replace('/[^a-z0-9-]/', '_', substr($path, strlen(ROOT_DIR)));
                $data .= '<li class="collapsible-header dir">
                            <a data-toggle="collapse" href="#' . htmlspecialchars($dir_id) . '">
                                <i class="fas fa-folder fa-sm folder-icon"></i> ' . htmlspecialchars($file) . '
                            </a>
                            <div class="collapsible-body ' . ($selected ? 'expand' : 'collapse') . '" id="' . htmlspecialchars($dir_id) . '">
                                ' . files_tree($path, $current, $dot_files) . '
                            </div>
                        </li>';
            } else {
                $data .= '<li class="file ' . ($selected ? 'selected' : '') . '">
                            <i class="far fa-sm fa-file"></i> ';
                if (is_editable($file)) {
                    $data .= '<a href="?page=file_editor&file=' . urlencode(str_replace(ROOT_DIR, '', $path)) . '">' . htmlspecialchars(mb_strimwidth($file, 0, 22, "...")) . '</a>';
                } else {
                    $data .= htmlspecialchars($file);
                }
                $data .= '</li>';
            }
        }

        $data .= '</ul>';
        return $data;
    }

	$file = App::GET('file');

	if ($file) {
		$file = realpath(ROOT_DIR.DIRECTORY_SEPARATOR.$file);

		if ($file) {
			$ext = pathinfo($file, PATHINFO_EXTENSION);
			$rel_file = substr($file, strlen(ROOT_DIR.DIRECTORY_SEPARATOR));

			if (substr($file, 0, strlen(ROOT_DIR)) !== ROOT_DIR) {
				// Fichier hors limite, on annule
				$file = false;
			}
			elseif (!is_editable($file)) {
				// Extension non editable
				$file = false;
			}
		}
	}

    if ($file && App::POST('save') && App::POST('content') !== null) {
        $updated = file_put_contents($file, App::POST('content'));
		if ($updated) {
            App::setSuccess(__('admin/system.editor_alert_success_save'));
        } else {
        	App::setWarning(__('admin/system.editor_alert_warning_save'));
        }
	}

	if (App::POST('theme')) {
		App::setConfig('file_editor.theme', App::POST('theme'));
	}

	if (App::POST('fontSize')) {
		App::setConfig('file_editor.fontSize', App::POST('fontSize'));
	}

	$themes = [
		'Bright' => new HtmlSelectGroup([
			'ace/theme/chrome'                     => 'Chrome',
			'ace/theme/clouds'                     => 'Clouds',
			'ace/theme/crimson_editor'             => 'Crimson Editor',
			'ace/theme/dawn'                       => 'Dawn',
			'ace/theme/dreamweaver'                => 'Dreamweaver',
			'ace/theme/eclipse'                    => 'Eclipse',
			'ace/theme/github'                     => 'GitHub',
			'ace/theme/iplastic'                   => 'IPlastic',
			'ace/theme/solarized_light'            => 'Solarized Light',
			'ace/theme/textmate'                   => 'TextMate',
			'ace/theme/tomorrow'                   => 'Tomorrow',
			'ace/theme/xcode'                      => 'XCode',
			'ace/theme/kuroir'                     => 'Kuroir',
			'ace/theme/katzenmilch'                => 'KatzenMilch',
			'ace/theme/sqlserver'                  => 'SQL Server',
		]),
		'Dark' => new HtmlSelectGroup([
			'ace/theme/ambiance'                   => 'Ambiance',
			'ace/theme/chaos'                      => 'Chaos',
			'ace/theme/clouds_midnight'            => 'Clouds Midnight',
			'ace/theme/dracula'                    => 'Dracula',
			'ace/theme/cobalt'                     => 'Cobalt',
			'ace/theme/gruvbox'                    => 'Gruvbox',
			'ace/theme/gob'                        => 'Green on Black',
			'ace/theme/idle_fingers'               => 'idle Fingers',
			'ace/theme/kr_theme'                   => 'krTheme',
			'ace/theme/merbivore'                  => 'Merbivore',
			'ace/theme/merbivore_soft'             => 'Merbivore Soft',
			'ace/theme/mono_industrial'            => 'Mono Industrial',
			'ace/theme/monokai'                    => 'Monokai',
			'ace/theme/pastel_on_dark'             => 'Pastel on dark',
			'ace/theme/solarized_dark'             => 'Solarized Dark',
			'ace/theme/terminal'                   => 'Terminal',
			'ace/theme/tomorrow_night'             => 'Tomorrow Night',
			'ace/theme/tomorrow_night_blue'        => 'Tomorrow Night Blue',
			'ace/theme/tomorrow_night_bright'      => 'Tomorrow Night Bright',
			'ace/theme/tomorrow_night_eighties'    => 'Tomorrow Night 80s',
			'ace/theme/twilight'                   => 'Twilight',
			'ace/theme/vibrant_ink'                => 'Vibrant Ink',
		]),
	];
?>

<div id="file_editor" class="row">
	<div id="files_tree">
		<?= files_tree(ROOT_DIR, $file); ?>
	</div>
	<div id="files_edit">
		<form method="post">
			<?php if ($file) { ?>
				<div class='action'>
					<ol class='breadcrumb'>
						<li><i class='fas fa-folder fa-sm folder-icon'></i></li>
						<?php
							foreach(explode(DIRECTORY_SEPARATOR, $rel_file) as $value) {
								echo "<li>{$value}</li>";
							}
						?>
					</ol>
					<div class='buttons'>
						<?= Widgets::select('theme', $themes, App::getConfig('file_editor.theme'), true, '') ?>
						<?php if (!is_writable($file)) { echo __('admin/system.editor_alert_readonly'); } ?>
						<button type='submit' name='save' value='1' class='btn btn-success' title='<?= __('admin/system.editor_btn_save_title') ?>'><i class='fas fa-save'></i> <?= __('admin/system.editor_btn_save') ?></button>
						<button type="button" name="fe-upload-file" class="btn btn-primary" data-toggle="modal" data-target="upload" title="<?= __('admin/system.editor_btn_upload_title') ?>"><i class="fas fa-file-import"></i></button>
                        <div class="btn-group" role="group" aria-label="zoom">
                          <button name="fe-zoom-in" class="btn btn-secondary" title="<?= __('admin/system.editor_btn_zoomin') ?>"><i class="fas fa-search-plus"></i></button>
                          <span name="font-size" class="btn btn-secondary" disabled></span>
                          <button name="fe-zoom-out" class="btn btn-secondary" title="<?= __('admin/system.editor_btn_zoomout') ?>"><i class="fas fa-search-minus"></i></button>
                        </div>
					</div>
				</div>
				<textarea name="content"><?= html_encode(file_get_contents($file)); ?></textarea>
				<div id="code_editor"></div>
				<script type="text/javascript">
					var editor = ace.edit("code_editor");
					var textarea = $('textarea[name="content"]').hide();
					editor.session.setMode("ace/mode/<?= $ext ?>");
					editor.setTheme('<?=App::getConfig('file_editor.theme') ?>');
					editor.setOption("fontSize", '<?=App::getConfig('file_editor.fontSize') ?>px');
					editor.setOption("showPrintMargin", false);
					editor.setOption("wrap", true);
					editor.getSession().setValue(textarea.val());
					editor.getSession().on('change', function(){
						textarea.val(editor.getSession().getValue());
					});
					editor.commands.addCommand({
						name: 'save',
						bindKey: {win: "Ctrl-S", "mac": "Cmd-S"},
						exec: function(editor) {
							$('button[name="save"]').click();
						}
					});

					$('select[name="theme"]').change(function() {
						$.post('', {theme: this.value, csrf});
						editor.setTheme(this.value);
					});

					$('span[name=font-size]').html(parseInt($("#code_editor").css("font-size")));

					$("button[name=fe-zoom-in]").click(function(){
					    var fsize = parseInt($("#code_editor").css("font-size")) + 1;
						$("#code_editor").css("font-size", fsize + "px");
					    $("span[name=font-size]").html(fsize);
					    $.post('', {fontSize: fsize, csrf});
					    return false;
					});

					$("button[name=fe-zoom-out]").click(function(){
					    var fsize = parseInt($("#code_editor").css("font-size")) - 1;
					    $("#code_editor").css("font-size", fsize + "px");
					    $("span[name=font-size]").html(fsize);
					    $.post('', {fontSize: fsize, csrf});
					    return false;
					});
				</script>
			<?php } else { ?>
				<?= __('admin/system.editor_background_main') ?>
			<?php } ?>
		</form>
	</div>
</div>
