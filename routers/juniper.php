<?php

/*
 * Looking Glass - An easy to deploy Looking Glass
 * Copyright (C) 2014-2024 Guillaume Mazoyer <guillaume@mazoyer.eu>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software Foundation,
 * Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301  USA
 */

require_once('router.php');
require_once('includes/command_builder.php');
require_once('includes/utils.php');

final class Juniper extends Router {
  protected function build_bgp($parameter, $vrf = false) {
    $cmd = new CommandBuilder();
    $cmd->add('show route', $parameter, 'protocol bgp table');

    if($vrf === false) {
      if (match_ipv6($parameter, false)) {
          $cmd->add('inet6.0');
      }
      if (match_ipv4($parameter, false)) {
          $cmd->add('inet.0');
      }
    } else {
      $cmd->add($vrf);
    }

    if ($this->config['bgp_detail']) {
      $cmd->add('detail');
    }

    return array($cmd);
  }

  protected function build_aspath_regexp($parameter, $vrf = false) {
    $parameter = quote($parameter);
    $commands = array();
    $cmd = new CommandBuilder();
    $cmd->add('show route aspath-regex', $parameter, 'protocol bgp table');

    if (!$this->config['disable_ipv6']) {
      $cmd6 = clone $cmd;
      if(!$vrf) {
        $cmd6->add('inet6.0');
      } else {
        $cmd6->add($vrf);
      }
      if ($this->config['bgp_detail']) {
        $cmd6->add('detail');
      }
      $commands[] = $cmd6;
    }
    if (!$this->config['disable_ipv4']) {
      $cmd4 = clone $cmd;
      if (!$vrf) {
        $cmd4->add('inet.0');
      } else {
        $cmd4->add($vrf);
      }
      if ($this->config['bgp_detail']) {
        $cmd4->add('detail');
      }
      $commands[] = $cmd4;
    }

    return $commands;
  }

  protected function build_as($parameter, $vrf = false) {
    $parameter = '^'.$parameter.' .*';
    return $this->build_aspath_regexp($parameter, $vrf);
  }

  protected function build_ping($parameter, $vrf = false) {
    if (!is_valid_destination($parameter)) {
      throw new Exception('The parameter is not an IP address or a hostname.');
    }

    $cmd = new CommandBuilder();
    $cmd->add('ping count 10 rapid', $parameter);

    if ($vrf !== false) {
      $vrf = $this->strip_suffix_from_vrf($vrf);
      $cmd->add('routing-instance ' . $vrf);
    }

    if ($this->has_source_interface_id()) {
      $cmd->add('interface', $this->get_source_interface_id());
    }

    return array($cmd);
  }

  protected function build_traceroute($parameter, $vrf = false) {
    if (!is_valid_destination($parameter)) {
      throw new Exception('The parameter is not an IP address or a hostname.');
    }

    $cmd = new CommandBuilder();
    $cmd->add('traceroute');

    if ($vrf !== false) {
      $vrf = $this->strip_suffix_from_vrf($vrf);
      $cmd->add('routing-instance ' . $vrf);
    }

    if (match_ipv4($parameter)) {
      $cmd->add('as-number-lookup');
    }
    $cmd->add($parameter);


    if ($this->has_source_interface_id()) {
      $cmd->add('interface', $this->get_source_interface_id());
    }

    return array($cmd);
  }
}

// End of juniper.php
