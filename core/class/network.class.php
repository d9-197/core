<?php

/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* * ***************************Includes********************************* */
require_once __DIR__ . '/../../core/php/core.inc.php';

class network {

	public static function getUserLocation() {
		$client_ip = self::getClientIp();
		$jeedom_ip = self::getNetworkAccess('internal', 'ip', '', false);
		if (!filter_var($jeedom_ip, FILTER_VALIDATE_IP)) {
			return 'external';
		}
		$jeedom_ips = explode('.', $jeedom_ip);
		if (count($jeedom_ips) != 4) {
			return 'external';
		}
		if (config::byKey('network::localip') != '') {
			$localIps = explode(';', config::byKey('network::localip'));
			foreach ($localIps as $localIp) {
				if (netMatch($localIp, $client_ip)) {
					return 'internal';
				}
			}
		}
		$match = $jeedom_ips[0] . '.' . $jeedom_ips[1] . '.' . $jeedom_ips[2] . '.*';
		return netMatch($match, $client_ip) ? 'internal' : 'external';
	}

	public static function getClientIp() {
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			return $_SERVER['HTTP_X_FORWARDED_FOR'];
		} elseif (isset($_SERVER['HTTP_X_REAL_IP'])) {
			return $_SERVER['HTTP_X_REAL_IP'];
		} elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
			return $_SERVER['HTTP_CLIENT_IP'];
		} elseif (isset($_SERVER['REMOTE_ADDR'])) {
			return $_SERVER['REMOTE_ADDR'];
		}
		return '';
	}

	public static function getNetworkAccess($_mode = 'auto', $_protocol = '', $_default = '', $_test = false) {
		if ($_mode == 'auto') {
			$_mode = self::getUserLocation();
		}
		if ($_mode == 'internal' && config::byKey('internalAddr', 'core', '') == '') {
			self::checkConf($_mode);
		}
		if ($_mode == 'external' && config::byKey('market::allowDNS') != 1 && config::byKey('externalAddr', 'core', '') == '') {
			self::checkConf($_mode);
		}
		if ($_test && !self::test($_mode)) {
			self::checkConf($_mode);
		}
		if ($_mode == 'internal') {
			if (strpos(config::byKey('internalAddr', 'core', $_default), 'http://') !== false || strpos(config::byKey('internalAddr', 'core', $_default), 'https://') !== false) {
				config::save('internalAddr', str_replace(array('http://', 'https://'), '', config::byKey('internalAddr', 'core', $_default)));
			}
			if ($_protocol == 'ip' || $_protocol == 'dns') {
				return config::byKey('internalAddr', 'core', $_default);
			}
			if ($_protocol == 'ip:port' || $_protocol == 'dns:port') {
				return config::byKey('internalAddr') . ':' . config::byKey('internalPort', 'core', 80);
			}
			if ($_protocol == 'proto:ip' || $_protocol == 'proto:dns') {
				return config::byKey('internalProtocol') . config::byKey('internalAddr');
			}
			if ($_protocol == 'proto:ip:port' || $_protocol == 'proto:dns:port') {
				return config::byKey('internalProtocol') . config::byKey('internalAddr') . ':' . config::byKey('internalPort', 'core', 80);
			}
			if ($_protocol == 'proto:127.0.0.1:port:comp') {
				if (jeedom::getHardwareName() == 'docker') {
					return trim(config::byKey('internalProtocol') . config::byKey('internalAddr') . ':' . config::byKey('internalPort', 'core', 80) . '/' . trim(config::byKey('internalComplement'), '/'), '/');
				}
				return trim(config::byKey('internalProtocol') . '127.0.0.1:' . config::byKey('internalPort', 'core', 80) . '/' . trim(config::byKey('internalComplement'), '/'), '/');
			}
			if ($_protocol == 'http:127.0.0.1:port:comp') {
				if (jeedom::getHardwareName() == 'docker') {
					return trim(config::byKey('internalProtocol') . config::byKey('internalProtocol') . ':' . config::byKey('internalPort', 'core', 80) . '/' . trim(config::byKey('internalComplement'), '/'), '/');
				}
				return trim('http://127.0.0.1:' . config::byKey('internalPort', 'core', 80) . '/' . trim(config::byKey('internalComplement'), '/'), '/');
			}
			if (config::byKey('internalPort', 'core', '') == '') {
				return trim(config::byKey('internalProtocol') . config::byKey('internalAddr') .  '/' . trim(config::byKey('internalComplement'), '/'), '/');
			}
			if (config::byKey('internalProtocol') == 'http://' && config::byKey('internalPort', 'core', 80) == 80) {
				return trim(config::byKey('internalProtocol') . config::byKey('internalAddr') .  '/' . trim(config::byKey('internalComplement'), '/'), '/');
			}
			if (config::byKey('internalProtocol') == 'https://' && config::byKey('internalPort', 'core', 443) == 443) {
				return trim(config::byKey('internalProtocol') . config::byKey('internalAddr') .  '/' . trim(config::byKey('internalComplement'), '/'), '/');
			}
			return trim(config::byKey('internalProtocol') . config::byKey('internalAddr') . ':' . config::byKey('internalPort', 'core', 80) . '/' . trim(config::byKey('internalComplement'), '/'), '/');
		}
		if ($_mode == 'dnsjeedom') {
			return config::byKey('jeedom::url');
		}
		if ($_mode == 'external') {
			if ($_protocol == 'ip') {
				if (config::byKey('market::allowDNS') == 1 && config::byKey('jeedom::url') != '' && config::byKey('network::disableMangement') == 0) {
					return getIpFromString(config::byKey('jeedom::url'));
				}
				return getIpFromString(config::byKey('externalAddr'));
			}
			if ($_protocol == 'ip:port') {
				if (config::byKey('market::allowDNS') == 1 && config::byKey('jeedom::url') != '' && config::byKey('network::disableMangement') == 0) {
					$url = parse_url(config::byKey('jeedom::url'));
					if (isset($url['host'])) {
						if (isset($url['port'])) {
							return getIpFromString($url['host']) . ':' . $url['port'];
						} else {
							return getIpFromString($url['host']);
						}
					}
				}
				return config::byKey('externalAddr') . ':' . config::byKey('externalPort', 'core', 80);
			}
			if ($_protocol == 'proto:dns:port' || $_protocol == 'proto:ip:port') {
				if (config::byKey('market::allowDNS') == 1 && config::byKey('jeedom::url') != '' && config::byKey('network::disableMangement') == 0) {
					$url = parse_url(config::byKey('jeedom::url'));
					$return = '';
					if (isset($url['scheme'])) {
						$return = $url['scheme'] . '://';
					}
					if (isset($url['host'])) {
						if (isset($url['port'])) {
							return $return . $url['host'] . ':' . $url['port'];
						} else {
							return $return . $url['host'];
						}
					}
				}
				return config::byKey('externalProtocol') . config::byKey('externalAddr') . ':' . config::byKey('externalPort', 'core', 80);
			}
			if ($_protocol == 'proto:dns' || $_protocol == 'proto:ip') {
				if (config::byKey('market::allowDNS') == 1 && config::byKey('jeedom::url') != '' && config::byKey('network::disableMangement') == 0) {
					$url = parse_url(config::byKey('jeedom::url'));
					$return = '';
					if (isset($url['scheme'])) {
						$return = $url['scheme'] . '://';
					}
					if (isset($url['host'])) {
						if (isset($url['port'])) {
							return $return . $url['host'];
						} else {
							return $return . $url['host'];
						}
					}
				}
				return config::byKey('externalProtocol') . config::byKey('externalAddr');
			}
			if ($_protocol == 'dns:port') {
				if (config::byKey('market::allowDNS') == 1 && config::byKey('jeedom::url') != '' && config::byKey('network::disableMangement') == 0) {
					$url = parse_url(config::byKey('jeedom::url'));
					if (isset($url['host'])) {
						if (isset($url['port'])) {
							return $url['host'] . ':' . $url['port'];
						} else {
							return $url['host'];
						}
					}
				}
				return config::byKey('externalAddr') . ':' . config::byKey('externalPort', 'core', 80);
			}
			if ($_protocol == 'proto') {
				if (config::byKey('market::allowDNS') == 1 && config::byKey('jeedom::url') != '' && config::byKey('network::disableMangement') == 0) {
					$url = parse_url(config::byKey('jeedom::url'));
					if (isset($url['scheme'])) {
						return $url['scheme'] . '://';
					}
				}
				return config::byKey('externalProtocol');
			}

			if (config::byKey('dns::token') != '' && config::byKey('market::allowDNS') == 1 && config::byKey('jeedom::url') != '' && config::byKey('network::disableMangement') == 0) {
				return trim(config::byKey('jeedom::url') . '/' . trim(config::byKey('externalComplement', 'core', ''), '/'), '/');
			}
			if (config::byKey('externalPort', 'core', '') == '') {
				return trim(config::byKey('externalProtocol') . config::byKey('externalAddr') . '/' . trim(config::byKey('externalComplement'), '/'), '/');
			}
			if (config::byKey('externalProtocol') == 'http://' && config::byKey('externalPort', 'core', 80) == 80) {
				return trim(config::byKey('externalProtocol') . config::byKey('externalAddr') . '/' . trim(config::byKey('externalComplement'), '/'), '/');
			}
			if (config::byKey('externalProtocol') == 'https://' && config::byKey('externalPort', 'core', 443) == 443) {
				return trim(config::byKey('externalProtocol') . config::byKey('externalAddr') . '/' . trim(config::byKey('externalComplement'), '/'), '/');
			}
			return trim(config::byKey('externalProtocol') . config::byKey('externalAddr') . ':' . config::byKey('externalPort', 'core', 80) . '/' . trim(config::byKey('externalComplement'), '/'), '/');
		}
	}

	public static function checkConf($_mode = 'external') {
		if (config::byKey($_mode . 'Protocol') == '') {
			config::save($_mode . 'Protocol', 'http://');
		}
		if (config::byKey($_mode . 'Port') == '') {
			config::save($_mode . 'Port', 80);
		}
		if (config::byKey($_mode . 'Protocol') == 'https://' && config::byKey($_mode . 'Port') == 80) {
			config::save($_mode . 'Port', 443);
		}
		if (config::byKey($_mode . 'Protocol') == 'http://' && config::byKey($_mode . 'Port') == 443) {
			config::save($_mode . 'Port', 80);
		}
		if (trim(config::byKey($_mode . 'Complement')) == '/') {
			config::save($_mode . 'Complement', '');
		}
		if ($_mode == 'internal') {
			foreach ((self::getInterfacesInfo()) as $interface) {
				if ($interface['ifname'] == 'lo' || !isset($interface['addr_info']) || strpos($interface['ifname'], 'docker') !== false  || strpos($interface['ifname'], 'tun') !== false || strpos($interface['ifname'], 'br') !== false) {
					continue;
				}
				$ip = null;
				foreach ($interface['addr_info'] as $addr_info) {
					if (isset($addr_info['family']) && $addr_info['family'] == 'inet') {
						$ip = $addr_info['local'];
					}
				}
				if ($ip == null) {
					continue;
				}
				if (!netMatch('127.0.*.*', $ip) && !netMatch('169.*.*.*', $ip) && $ip != '' && filter_var($ip, FILTER_VALIDATE_IP)) {
					config::save('internalAddr', $ip);
					break;
				}
			}
		}
	}

	public static function test($_mode = 'external', $_timeout = 15) {
		if (config::byKey('network::disableMangement') == 1 && $_mode == 'external') {
			return true;
		}
		if ($_mode == 'internal' && netMatch('127.0.*.*', self::getNetworkAccess($_mode, 'ip', '', false))) {
			return false;
		}
		$url = trim(self::getNetworkAccess($_mode, '', '', false), '/') . '/here.html';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_TIMEOUT, $_timeout);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		if ($_mode == 'external') {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		}
		$data = curl_exec($ch);
		if (curl_errno($ch)) {
			usleep(rand(1000, 100000));
			$data = curl_exec($ch);
			if (curl_errno($ch)) {
				log::add('network', 'debug', 'Erreur sur ' . $url . ' => ' . curl_errno($ch));
				curl_close($ch);
				return false;
			}
		}
		curl_close($ch);
		if (trim($data) != 'ok') {
			log::add('network', 'debug', 'Retour NOK sur ' . $url . ' => ' . $data);
			return false;
		}
		return true;
	}

	/*     * *********************DNS************************* */

	public static function dns_create() {
		if (config::byKey('dns::token') == '') {
			return;
		}
		if (!in_array(config::byKey('dns::mode'), array('openvpn', 'wireguard'))) {
			config::save('dns::mode', 'openvpn');
		}
		if (config::byKey('dns::mode') == 'openvpn') {
			$wireguard = eqLogic::byLogicalId('dnsjeedom', 'wireguard');
			if (is_object($wireguard)) {
				$wireguard->remove();
			}
			try {
				$plugin = plugin::byId('openvpn');
				if (!is_object($plugin)) {
					$update = update::byLogicalId('openvpn');
					if (!is_object($update)) {
						$update = new update();
					}
					$update->setLogicalId('openvpn');
					$update->setSource('market');
					$update->setConfiguration('version', 'stable');
					$update->save();
					$update->doUpdate();
					$plugin = plugin::byId('openvpn');
				}
			} catch (Exception $e) {
				$update = update::byLogicalId('openvpn');
				if (!is_object($update)) {
					$update = new update();
				}
				$update->setLogicalId('openvpn');
				$update->setSource('market');
				$update->setConfiguration('version', 'stable');
				$update->save();
				$update->doUpdate();
				$plugin = plugin::byId('openvpn');
			}
			if (!is_object($plugin)) {
				throw new Exception(__('Le plugin OpenVPN doit être installé', __FILE__));
			}
			if (!$plugin->isActive()) {
				$plugin->setIsEnable(1);
				$plugin->dependancy_install();
			}
			if (!$plugin->isActive()) {
				throw new Exception(__('Le plugin OpenVPN doit être actif', __FILE__));
			}
			$openvpn = eqLogic::byLogicalId('dnsjeedom', 'openvpn');
			$direct = true;
			if (!is_object($openvpn)) {
				$direct = false;
				$openvpn = new openvpn();
				$openvpn->setName('DNS Jeedom');
			}
			$openvpn->setIsEnable(1);
			$openvpn->setLogicalId('dnsjeedom');
			$openvpn->setEqType_name('openvpn');
			$openvpn->setConfiguration('dev', 'tun');
			$openvpn->setConfiguration('proto', 'udp');
			if(strpos(config::byKey('dns::protocol'),config::byKey('dns::preferProtocol')) !== false && config::byKey('dns::preferProtocol') != ''){
				$openvpn->setConfiguration('proto', config::byKey('dns::preferProtocol'));
			}
			if (config::byKey('dns::vpnurl') != '') {
				$openvpn->setConfiguration('remote_host', config::byKey('dns::vpnurl'));
			} else {
				$openvpn->setConfiguration('remote_host', 'vpn.dns' . config::byKey('dns::number', 'core', 1) . '.jeedom.com');
			}
			if (config::byKey('dns::remote') != '') {
				$openvpn->setConfiguration('remote', config::byKey('dns::remote'));
			}
			$openvpn->setConfiguration('username', jeedom::getHardwareKey());
			$openvpn->setConfiguration('password', config::byKey('dns::token'));
			$openvpn->setConfiguration('compression', 'comp-lzo');
			$openvpn->setConfiguration('remote_port', config::byKey('vpn::port', 'core', 1194));
			$openvpn->setConfiguration('auth_mode', 'password');
			$openvpn->save($direct);
			if (!file_exists(__DIR__ . '/../../plugins/openvpn/data')) {
				shell_exec('mkdir -p ' . __DIR__ . '/../../plugins/openvpn/data');
			}
			$path_ca = __DIR__ . '/../../plugins/openvpn/data/ca_' . $openvpn->getConfiguration('key') . '.crt';
			if (file_exists($path_ca)) {
				unlink($path_ca);
			}
			copy(__DIR__ . '/../../resources/ca_dns.crt', $path_ca);
			if (!file_exists($path_ca)) {
				throw new Exception(__('Impossible de créer le fichier  :', __FILE__) . ' ' . $path_ca);
			}
			return $openvpn;
		} elseif (config::byKey('dns::mode') == 'wireguard') {
			$openvpn = eqLogic::byLogicalId('dnsjeedom', 'openvpn');
			if (is_object($openvpn)) {
				$openvpn->remove();
			}
			try {
				$plugin = plugin::byId('wireguard');
				if (!is_object($plugin)) {
					$update = update::byLogicalId('wireguard');
					if (!is_object($update)) {
						$update = new update();
					}
					$update->setLogicalId('wireguard');
					$update->setSource('market');
					$update->setConfiguration('version', 'stable');
					$update->save();
					$update->doUpdate();
					$plugin = plugin::byId('wireguard');
				}
			} catch (Exception $e) {
				$update = update::byLogicalId('wireguard');
				if (!is_object($update)) {
					$update = new update();
				}
				$update->setLogicalId('wireguard');
				$update->setSource('market');
				$update->setConfiguration('version', 'stable');
				$update->save();
				$update->doUpdate();
				$plugin = plugin::byId('wireguard');
			}
			if (!is_object($plugin)) {
				throw new Exception(__('Le plugin Wireguard doit être installé', __FILE__));
			}
			if (!$plugin->isActive()) {
				$plugin->setIsEnable(1);
				$plugin->dependancy_install();
			}
			if (!$plugin->isActive()) {
				throw new Exception(__('Le plugin Wireguard doit être actif', __FILE__));
			}
			$wireguard = eqLogic::byLogicalId('dnsjeedom', 'wireguard');
			$direct = true;
			if (!is_object($wireguard)) {
				$direct = false;
				$wireguard = new wireguard();
				$wireguard->setName('DNS Jeedom');
			}
			$wireguard->setIsEnable(1);
			$wireguard->setLogicalId('dnsjeedom');
			$wireguard->setEqType_name('wireguard');
			$wireguard->save($direct);
			return $wireguard;
		}
	}


	public static function dns_start() {
		if (config::byKey('dns::token') == '') {
			return;
		}
		if (config::byKey('market::allowDNS') != 1) {
			return;
		}
		$vpn = self::dns_create();
		$cmd = $vpn->getCmd('action', 'start');
		if (!is_object($cmd)) {
			throw new Exception(__('La commande de démarrage du DNS est introuvable', __FILE__));
		}
		$cmd->execCmd();
	}

	public static function dns_run() {
		if (config::byKey('dns::token') == '') {
			return false;
		}
		if (config::byKey('market::allowDNS') != 1) {
			return false;
		}
		try {
			$vpn = self::dns_create();
		} catch (Exception $e) {
			return false;
		}
		$cmd = $vpn->getCmd('info', 'state');
		if (!is_object($cmd)) {
			throw new Exception(__('La commande de statut du DNS est introuvable', __FILE__));
		}
		return $cmd->execCmd();
	}

	public static function dns_stop() {
		if (config::byKey('dns::token') == '') {
			return;
		}
		$vpn = self::dns_create();
		$cmd = $vpn->getCmd('action', 'stop');
		if (!is_object($cmd)) {
			throw new Exception(__('La commande d\'arrêt du DNS est introuvable', __FILE__));
		}
		$cmd->execCmd();
	}

	/*     * *********************Network management************************* */

	public static function portOpen($host, $port) {
		$fp = @fsockopen($host, $port, $errno, $errstr, 0.1);
		if (!is_resource($fp)) {
			return false;
		}
		fclose($fp);
		return true;
	}

	public static function getInterfacesInfo() {
		return json_decode(shell_exec(system::getCmdSudo() . "ip -j a"), true);
	}

	public static function cron10() {
		if (config::byKey('dns::token') != '' && config::byKey('market::allowDNS') == 1) {
			sleep(rand(0, 240));
			if (!network::test('external')) {
				sleep(rand(20, 60));
				if (!network::test('external')) {
					log::add('network', 'warning', __('Accès externe non ok, redémarrage du dns Jeedom', __FILE__));
					self::dns_stop();
					self::dns_start();
				}
			}
		}
		if (config::byKey('network::disableMangement') == 1) {
			return;
		}
		if (!jeedom::isCapable('sudo') || jeedom::getHardwareName() == 'docker') {
			return;
		}
		exec(system::getCmdSudo() . 'ping -n -c 1 -t 255 8.8.8.8 2>&1 > /dev/null', $output, $return_val);
		if ($return_val == 0) {
			return;
		}
		$gw = shell_exec("ip route show default | awk '/default/ {print $3}'");
		if ($gw == '') {
			log::add('network', 'error', __('Souci réseau détecté, redémarrage du réseau. Aucune gateway de trouvée', __FILE__));
			exec(system::getCmdSudo() . 'service networking restart');
			return;
		}
		exec(system::getCmdSudo() . 'ping -n -c 1 -t 255 ' . $gw . ' 2>&1 > /dev/null', $output, $return_val);
		if ($return_val == 0) {
			return;
		}
		exec(system::getCmdSudo() . 'ping -n -c 1 -t 255 ' . $gw . ' 2>&1 > /dev/null', $output, $return_val);
		if ($return_val == 0) {
			return;
		}
		log::add('network', 'error', __('Souci réseau détecté, redémarrage du réseau. La gateway ne répond pas au ping :', __FILE__) . ' ' . $gw);
		exec(system::getCmdSudo() . 'service networking restart');
	}
}
