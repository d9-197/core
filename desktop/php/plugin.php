<?php
if (!isConnect('admin')) {
  throw new Exception('{{401 - Accès non autorisé}}');
}
global $JEEDOM_INTERNAL_CONFIG;

sendVarToJS([
  'jeephp2js.selPluginId' => init('id', '-1'),
  'jeephp2js.pluginCategories' => $JEEDOM_INTERNAL_CONFIG['plugin']['category']
]);
$plugins_list = plugin::listPlugin(false, true);
?>

<div class="row row-overflow">
  <div class="col-xs-12" id="div_resumePluginList" <?php if (init('id', '-1') != -1) echo 'style="display:none;"'?>>
    <legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
    <div class="pluginListContainer <?php echo (jeedom::getThemeConfig()['theme_displayAsTable'] == 1) ? ' containerAsTable' : ''; ?>">
      <?php
      $div = '';
      foreach ((update::listRepo()) as $key => $value) {
        if (!$value['enable']) {
          continue;
        }
        if (!isset($value['scope']['pullInstall']) || !$value['scope']['pullInstall']) {
          continue;
        }
        $div .= '<div class="cursor pullInstall success" data-repo="' . $key . '">';
        $div .= '<div class="center"><i class="fas fa-sync"></i></div>';
        $div .= '<span class="txtColor">{{Synchroniser}} ' . $value['name'] . '</span>';
        $div .= '</div>';
        if (!isset($value['scope']['hasStore']) || !$value['scope']['hasStore']) {
          continue;
        }
        if(isset($value['scope']['urlStore'])){
          $div .= '<div class="cursor success gotoUrlStore" data-href="'.config::byKey($key.'::'.$value['scope']['urlStore']).'">';
          $div .= '<div class="center"><i class="fas fa-shopping-cart"></i></div>';
          $div .= '<span class="txtColor">' . $value['name'] . '</span>';
          $div .= '</div>';
        }else{
          $div .= '<div class="cursor displayStore success" data-repo="' . $key . '">';
          $div .= '<div class="center"><i class="fas fa-shopping-cart"></i></div>';
          $div .= '<span class="txtColor">' . $value['name'] . '</span>';
          $div .= '</div>';
        }
      }
      echo $div;
      ?>
      <div class="cursor success" id="bt_addPluginFromOtherSource">
        <div class="center"><i class="fas fa-plus"></i></div>
        <span class="txtColor">{{Plugins}}</span>
      </div>
    </div>
    <legend><i class="fas fa-list-alt"></i> {{Mes plugins}} <sub class="itemsNumber"></sub></legend>
    <div class="input-group" style="margin-bottom:5px;">
      <input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchPlugin"/>
      <div class="input-group-btn">
        <a id="bt_resetPluginSearch" class="btn" style="width:30px"><i class="fas fa-times"></i>
        </a><a class="btn roundedRight" id="bt_displayAsTable" data-card=".pluginDisplayCard" data-container=".pluginListContainer" data-state="0"><i class="fas fa-grip-lines"></i></a>
      </div>
    </div>
    <div class="panel">
      <div class="panel-body">
        <div class="pluginListContainer">
          <?php
          foreach ((plugin::listPlugin()) as $plugin) {
            $inactive = ($plugin->isActive()) ? '' : 'inactive';
            if (jeedom::getThemeConfig()['theme_displayAsTable'] == 1) $inactive .= ' displayAsTable';
            $div = '<div class="pluginDisplayCard cursor '.$inactive.'" data-pluginPath="' . $plugin->getFilepath() . '" data-plugin_id="' . $plugin->getId() . '">';
            $div .= '<center>';
            $div .= '<img src="' . $plugin->getPathImgIcon() . '" />';
            $div .= '</center>';
            $lbl_version = false;
            $update = $plugin->getUpdate();
            if (is_object($update)) {
              $version = $update->getConfiguration('version');
              if ($version && $version != 'stable') $lbl_version = true;
            }
            if ($lbl_version) {
              $div .= '<span class="name"><sub style="font-size:22px" class="warning">&#8226</sub>' . $plugin->getName() . '</span>';
            } else {
              $div .= '<span class="name">' . $plugin->getName() . '</span>';
            }

            $div .= '<span class="hiddenAsCard displayTableRight">';
            $div .= '<span>'.$plugin->getSource() .' </span>';
            $div .= '<span>'.$plugin->getAuthor().' </span>';
            $div .= '<span>'.$plugin->getCategory().'</span>';
            $div .= ' <a class="btn btn-default btn-xs bt_openPluginPage"><i class="fas fa-share"></i></a>';
            $div .= '</span>';

            $div .= '</div>';
            echo $div;
          }
          ?>
        </div>
      </div>
    </div>

  </div>
  <div class="hasfloatingbar col-xs-12" id="div_confPlugin" style="display:none;">
    <div class="floatingbar">
      <div class="input-group">
        <span class="input-group-btn" id="span_right_button"></span>
      </div>
    </div>

    <legend>
      <i class="fas fa-arrow-circle-left cursor" id="bt_returnToThumbnailDisplay"></i>
      <span id="span_plugin_name"></span> (<span id="span_plugin_id"></span>) - <span id="span_plugin_install_version"></span>
    </legend>

    <div class="row">
      <div class="col-md-6 col-sm-12">
        <div class="panel panel-default" id="div_state">
          <div class="panel-heading">
            <h3 class="panel-title"><i class="fas fa-circle-notch"></i> {{Etat}} <a class="btn btn-info btn-xs pull-right bt_openPluginPage"><i class="fas fa-share"></i> {{Ouvrir}}</a></h3>
          </div>
          <div class="panel-body">
            <div id="div_plugin_toggleState"></div>
            <form class="form-horizontal">
              <fieldset>
                <div class="form-group">
                  <label class="col-sm-2 col-xs-6 control-label">{{Catégorie}}</label>
                  <div class="col-sm-4 col-xs-6">
                    <span id="span_plugin_category"></span>
                  </div>
                  <label class="col-sm-2 col-xs-6 control-label">{{Source}}</label>
                  <div class="col-sm-4 col-xs-6">
                    <span id="span_plugin_source"></span>
                  </div>
                </div>

                <div class="form-group">
                  <label class="col-sm-2 col-xs-6 control-label">{{Auteur}}</label>
                  <div class="col-sm-4 col-xs-6">
                    <span id="span_plugin_author"></span>
                  </div>
                  <label class="col-sm-2 col-xs-6 control-label">{{Version}}
                    <sup><i class="fas fa-question-circle" title="{{Version installée du plugin.}}"></i></sup>
                  </label>
                  <div class="col-sm-4 col-xs-6">
                    <span id="span_plugin_install_date"></span>
                  </div>
                </div>

                <div class="form-group">
                  <label class="col-sm-2 col-xs-6 control-label">{{License}}</label>
                  <div class="col-sm-4 col-xs-6">
                    <span id="span_plugin_license"></span>
                  </div>
                  <label class="col-sm-2 col-xs-6 control-label">{{Prérequis}}
                    <sup><i class="fas fa-question-circle" title="{{Version minimale du Core supportée par le plugin.}}"></i></sup>
                  </label>
                  <div class="col-sm-4 col-xs-6">
                    <span id="span_plugin_require"></span>
                  </div>
                </div>

              </fieldset>
            </form>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-sm-12">
        <div class="panel panel-primary" id="div_configLog">
          <div class="panel-heading">
            <h3 class="panel-title"><i class="far fa-file"></i> {{Logs et surveillance}}
              <a class="btn btn-success btn-xs pull-right" id="bt_savePluginLogConfig"><i class="fas fa-check-circle icon-white"></i> {{Sauvegarder}}</a>
            </h3>
          </div>
          <div class="panel-body">
            <form class="form-horizontal">
              <fieldset>
                <div id="div_plugin_log"></div>
              </fieldset>
            </form>
            <div class="form-actions"></div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6 col-sm-12">
        <div class="panel panel-success">
          <div class="panel-heading"><h3 class="panel-title"><i class="fas fa-certificate"></i> {{Dépendances}}</h3></div>
          <div class="panel-body">
            <div id="div_plugin_dependancy"></div>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-sm-12">
        <div class="panel panel-success">
          <div class="panel-heading"><h3 class="panel-title"><i class="fas fa-university"></i> {{Démon}}</h3></div>
          <div class="panel-body">
            <div id="div_plugin_deamon"></div>
          </div>
        </div>
      </div>
    </div>

    <div class="panel panel-primary">
      <div class="panel-heading"><h3 class="panel-title"><i class="fas fa-map"></i> {{Installation}}</h3></div>
      <div class="panel-body">
        <span id="span_plugin_installation"></span>
      </div>
    </div>

    <div class="panel panel-primary">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fas fa-cogs"></i> {{Configuration}}
          <a class="btn btn-success btn-xs pull-right" id="bt_savePluginConfig"><i class="fas fa-check-circle icon-white"></i> {{Sauvegarder}}</a>
        </h3>
      </div>
      <div class="panel-body">
        <div id="div_plugin_configuration"></div>
        <div class="form-actions"></div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6 col-sm-12">
        <div class="panel panel-primary" id="div_functionalityPanel">
          <div class="panel-heading">
            <h3 class="panel-title"><i class="fas fa-satellite"></i> {{Fonctionnalités}}
              <a class="btn btn-success btn-xs pull-right" id="bt_savePluginFunctionalityConfig"><i class="fas fa-check-circle icon-white"></i> {{Sauvegarder}}</a>
            </h3>
          </div>
          <div class="panel-body">
            <form class="form-horizontal">
              <fieldset>
                <div id="div_plugin_functionality"></div>
              </fieldset>
            </form>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-sm-12">
        <div class="panel panel-primary" id="div_configPanel">
          <div class="panel-heading">
            <h3 class="panel-title"><i class="fas fa-chalkboard"></i> {{Panel}}
              <a class="btn btn-success btn-xs pull-right" id="bt_savePluginPanelConfig"><i class="fas fa-check-circle icon-white"></i> {{Sauvegarder}}</a>
            </h3>
          </div>
          <div class="panel-body">
            <form class="form-horizontal">
              <fieldset>
                <div id="div_plugin_panel"></div>
              </fieldset>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include_file("desktop", "plugin", "js");?>
