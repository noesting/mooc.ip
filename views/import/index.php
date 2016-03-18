<h2><?=_("Datei hochladen")?></h2>
<?php
/** @var string[] $errors */

if (count($errors) > 0) {
	echo"<p>"._("Es sind Fehler aufgetreten:")."</p>";
    echo '<ul>';
    foreach ($errors as $error):
        echo '<li>'.htmlReady($error).'</li>';
    endforeach;
    echo '</ul>';
}
?>
<p>&nbsp;</p>
<p><?= _("Laden Sie eine Datei hoch, die Sie zuvor in einer MOOC.IP-Installation exportiert haben.")?></p>

<p>&nbsp;</p>
<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="subcmd" value="upload">
    <input type="file" name="import_file">

    <div>
        <?php
        echo Studip\Button::createAccept();
        echo Studip\Button::createCancel();
        ?>
    </div>
</form>

<p>&nbsp;</p>
<h2><?=_("Im Content-Marktplatz suchen")?></h2>
<p>&nbsp;</p>
<form method="POST">
    <input type="hidden" name="subcmd" value="search">
    <p><?=_("Stichwortsuche: ")?><input type="text" size=40 name="q" value="<?=Request::option('q')?>">

    <div>
        <?php
        echo Studip\Button::createAccept();
        ?>
    </div>
</form>

<? if (empty($modules)): ?>
    <?= MessageBox::info(_('Es wurden keine Plugins gefunden.')) ?>
<? else: ?>
    <h2><?=_("Suchtreffer:")?></h2>
    <table class="default">
        <tr>
            <th class="plugin_image"><?= _('Bild')?></th>
            <th><?= _('Name und Beschreibung')?></th>
            <th><?= _('Version') ?></th>
            <th><?= _('Bewertung') ?></th>
            <th class="plugin_install"><?= _('Installieren') ?></th>
        </tr>

        <? foreach ($modules as $name => $plugin): ?>
            <tr class="<?= TextHelper::cycle('hover_odd', 'hover_even') ?>">
                <td class="plugin_image">
                    <? if ($plugin['image']): ?>
                        <? if ($plugin['plugin_url']): ?>
                            <a href="<?= htmlReady($plugin['plugin_url']) ?>" target="_blank">
                                <img src="<?= htmlReady($plugin['image']) ?>" class="plugin_preview">
                            </a>
                        <? else: ?>
                            <img src="<?= htmlReady($plugin['image']) ?>" class="plugin_preview">
                        <? endif ?>
                    <? endif ?>
                </td>
                <td>
                    <? if ($plugin['plugin_url']): ?>
                        <a href="<?= htmlReady($plugin['plugin_url']) ?>" target="_blank">
                            <b><?= htmlReady(urldecode($name)) ?></b>
                        </a>
                    <? else: ?>
                        <b><?= htmlReady(urldecode($name)) ?></b>
                    <? endif ?>
                    <p>
                        <?= htmlReady($plugin['description']) ?>
                    </p>
                </td>
                <td>
                    <?= htmlReady($plugin['version']) ?>
                </td>
                <td class="plugin_score">
                    <? for ($i = 0; $i < $plugin['score']; ++$i): ?>
                        <?= Assets::img('icons/16/grey/star.png') ?>
                    <? endfor ?>
                </td>
                <td class="plugin_install">
                    <form method="post">
                        <input type="hidden" name="subcmd" value="install">
                        <input type="hidden" name="n" value="<?=htmlReady($name)?>">
                        <?= Assets::input("icons/16/blue/install.png", array('type' => "image", 'class' => "middle", 'name' => "install", 'title' => _('Plugin installieren'))) ?>
                    </form>
                </td>
            </tr>
        <? endforeach ?>
    </table>
<? endif ?>

<p>&nbsp;</p>

