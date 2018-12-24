<?php
/**
 * Xhprof报告字段含义
 * Function Name：方法名称。
 *
 * Calls：方法被调用的次数。
 *
 * Calls%：方法调用次数在同级方法总数调用次数中所占的百分比。
 *
 * Incl.Wall Time(microsec)：方法执行花费的时间，包括子方法的执行时间。（单位：微秒）
 *
 * IWall%：方法执行花费的时间百分比。
 *
 * Excl. Wall Time(microsec)：方法本身执行花费的时间，不包括子方法的执行时间。（单位：微秒）
 *
 * EWall%：方法本身执行花费的时间百分比。
 *
 * Incl. CPU(microsecs)：方法执行花费的CPU时间，包括子方法的执行时间。（单位：微秒）
 *
 * ICpu%：方法执行花费的CPU时间百分比。
 *
 * Excl. CPU(microsec)：方法本身执行花费的CPU时间，不包括子方法的执行时间。（单位：微秒）
 *
 * ECPU%：方法本身执行花费的CPU时间百分比。
 *
 * Incl.MemUse(bytes)：方法执行占用的内存，包括子方法执行占用的内存。（单位：字节）
 *
 * IMemUse%：方法执行占用的内存百分比。
 *
 * Excl.MemUse(bytes)：方法本身执行占用的内存，不包括子方法执行占用的内存。（单位：字节）
 *
 * EMemUse%：方法本身执行占用的内存百分比。
 *
 * Incl.PeakMemUse(bytes)：Incl.MemUse峰值。（单位：字节）
 *
 * IPeakMemUse%：Incl.MemUse峰值百分比。
 *
 * Excl.PeakMemUse(bytes)：Excl.MemUse峰值。单位：（字节）
 *
 * EPeakMemUse%：Excl.MemUse峰值百分比。
 */
namespace Xhprof\Controllers;
use Framework\Models\Controller;
use Xhprof\Libraries\XHProfRuns;

class Index extends Controller {
    public static $INDEX_PARAM_RULES = array(
        'run'    => '',
        'wts'    => '',
        'symbol' => '',
        'sort'   => '',
        'run1'   => '',
        'run2'   => '',
        'source' => '',
        'all'    => ''
    );
    public function indexAction() {
        $_GET  = $this->getGetParams();
        $_POST = $this->getPostParams();
        //  Copyright (c) 2009 Facebook
        //
        //  Licensed under the Apache License, Version 2.0 (the "License");
        //  you may not use this file except in compliance with the License.
        //  You may obtain a copy of the License at
        //
        //      http://www.apache.org/licenses/LICENSE-2.0
        //
        //  Unless required by applicable law or agreed to in writing, software
        //  distributed under the License is distributed on an "AS IS" BASIS,
        //  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
        //  See the License for the specific language governing permissions and
        //  limitations under the License.
        //

        // XHProf: A Hierarchical Profiler for PHP
        //
        // XHProf has two components:
        //
        //  * This module is the UI/reporting component, used
        //    for viewing results of XHProf runs from a browser.
        //
        //  * Data collection component: This is implemented
        //    as a PHP extension (XHProf).
        //
        //
        //
        // @author(s)  Kannan Muthukkaruppan
        //             Changhao Jiang
        //

        // by default assume that xhprof_html & xhprof_lib directories
        // are at the same level.

        $GLOBALS['XHPROF_LIB_ROOT'] = dirname(__FILE__) . '/../Libraries';
        require_once $GLOBALS['XHPROF_LIB_ROOT'] . '/display/xhprof.php';
        global $base_path;
        global $run;
        global $wts;
        global $symbol;
        global $sort;
        global $run1;
        global $run2;
        global $source;
        global $all;
        $base_path = '/xhprof';
        // param name, its type, and default value
        $params = array(
            'run'    => array(XHPROF_STRING_PARAM, ''),
            'wts'    => array(XHPROF_STRING_PARAM, ''),
            'symbol' => array(XHPROF_STRING_PARAM, ''),
            'sort'   => array(XHPROF_STRING_PARAM, 'wt'), // wall time
            'run1'   => array(XHPROF_STRING_PARAM, ''),
            'run2'   => array(XHPROF_STRING_PARAM, ''),
            'source' => array(XHPROF_STRING_PARAM, 'xhprof'),
            'all'    => array(XHPROF_UINT_PARAM, 0)
        );
        // pull values of these params, and create named globals for each param
        xhprof_param_init($params);

        /* reset params to be a array of variable names to values
        by the end of this page, param should only contain values that need
        to be preserved for the next page. unset all unwanted keys in $params.
         */
        foreach ($params as $k => $v) {
            $params[$k] = $$k;

            // unset key from params that are using default values. So URLs aren't
            // ridiculously long.
            if ($params[$k] == $v[1]) {
                unset($params[$k]);
            }
        }

        echo "<html>";

        echo "<head><title>XHProf: Hierarchical Profiler Report</title>";
        xhprof_include_js_css('/xhprof');
        echo "</head>";

        echo "<body>";

        $vbar   = ' class="vbar"';
        $vwbar  = ' class="vwbar"';
        $vwlbar = ' class="vwlbar"';
        $vbbar  = ' class="vbbar"';
        $vrbar  = ' class="vrbar"';
        $vgbar  = ' class="vgbar"';

        $xhprof_runs_impl = new XHProfRuns(LOG_ROOT_PATH.'/xhprof');
        displayXHProfReport($xhprof_runs_impl, $params, $source, $run, $wts,
            $symbol, $sort, $run1, $run2);

        echo "</body>";
        echo "</html>";

        return false;
    }
}
