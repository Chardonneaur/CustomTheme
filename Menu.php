<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CustomTheme;

use Piwik\Menu\MenuAdmin;
use Piwik\Piwik;

class Menu extends \Piwik\Plugin\Menu
{
    public function configureAdminMenu(MenuAdmin $menu): void
    {
        if (!Piwik::hasUserSuperUserAccess()) {
            return;
        }

        $menu->addSystemItem(
            'CustomTheme_CustomTheme',
            $this->urlForAction('index'),
            $order = 20
        );
    }
}
