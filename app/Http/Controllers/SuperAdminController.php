<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SuperAdminController extends Controller
{
    /**
     * Get total and occupied disk space.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDiskSpace()
    {
        // Get the root storage path (public path or storage path)
        $diskPath = storage_path();
        $totalSpace = @disk_total_space($diskPath);
        $freeSpace = @disk_free_space($diskPath);
        $occupiedSpace = $totalSpace - $freeSpace;

        return response()->json([
            'total_space_bytes' => $totalSpace,
            'occupied_space_bytes' => $occupiedSpace,
            'free_space_bytes' => $freeSpace,
            'percentage_occupied' => $totalSpace > 0 ? round(($occupiedSpace / $totalSpace) * 100, 2) : 0
        ]);
    }

    /**
     * Get MySQL CPU and Memory usage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMysqlUsage()
    {
        $cpuUsage = 0.0;
        $memoryUsage = 0; // in bytes

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows implementation
            $processName = 'mysqld'; // For PerfData, it's usually without .exe

            if (function_exists('shell_exec')) {
                // Use PowerShell to get both CPU and Memory for all mysqld processes
                $psCommand = 'powershell -NoProfile -Command "Get-CimInstance -ClassName Win32_PerfFormattedData_PerfProc_Process -Filter \\"Name LIKE \'' . $processName . '%\'\\" | Select-Object PercentProcessorTime, WorkingSet | ConvertTo-Json"';
                $psOutput = @shell_exec($psCommand);

                if ($psOutput) {
                    $data = json_decode($psOutput, true);
                    if ($data) {
                        // If it's a single object, make it an array
                        if (isset($data['PercentProcessorTime'])) {
                            $data = [$data];
                        }
                        
                        foreach ($data as $proc) {
                            if (isset($proc['PercentProcessorTime'])) $cpuUsage += floatval($proc['PercentProcessorTime']);
                            if (isset($proc['WorkingSet'])) $memoryUsage += intval($proc['WorkingSet']);
                        }
                    }
                }
            }
            
            // Fallback to WMIC if PowerShell failed or returned 0 for a specific metric
            if (function_exists('exec')) {
                if ($memoryUsage == 0) {
                    $output = [];
                    @exec('wmic process where "name like \'mysqld.exe\'" get WorkingSetSize /Value', $output);
                    $currentMemory = 0;
                    foreach ($output as $line) {
                        if (strpos($line, '=') !== false) {
                            $parts = explode('=', $line);
                            if (isset($parts[1]) && is_numeric(trim($parts[1]))) {
                                $currentMemory += intval(trim($parts[1]));
                            }
                        }
                    }
                    if ($currentMemory > 0) $memoryUsage = $currentMemory;
                }
                if ($cpuUsage == 0) {
                    $output = [];
                    @exec('wmic path Win32_PerfFormattedData_PerfProc_Process where "Name like \'mysqld%\'" get PercentProcessorTime /Value', $output);
                    $currentCpu = 0;
                    foreach ($output as $line) {
                        if (strpos($line, '=') !== false) {
                            $parts = explode('=', $line);
                            if (isset($parts[1]) && is_numeric(trim($parts[1]))) {
                                $currentCpu += floatval(trim($parts[1]));
                            }
                        }
                    }
                    if ($currentCpu > 0) $cpuUsage = $currentCpu;
                }
            }
        } else {
            // Linux implementation
            $processName = 'mysqld';
            if (function_exists('shell_exec')) {
                // Get total CPU and Memory (RSS in KB) for all mysqld processes
                $output = @shell_exec("ps -C {$processName} -o %cpu,rss --no-headers");
                if ($output) {
                    $lines = explode("\n", trim($output));
                    foreach ($lines as $line) {
                        $parts = preg_split('/\s+/', trim($line));
                        if (isset($parts[0])) $cpuUsage += floatval($parts[0]);
                        if (isset($parts[1])) $memoryUsage += intval($parts[1]) * 1024; // Convert KB to Bytes
                    }
                }
            }
        }

        return response()->json([
            'cpu_usage_percent' => $cpuUsage,
            'memory_usage_bytes' => $memoryUsage,
        ]);
    }

    /**
     * Get CPU Load.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCpuLoad()
    {
        $loadAverage = [0.0, 0.0, 0.0];
        $cpuUsagePercent = null;
        $os = 'unknown';

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $os = 'windows';
            // Windows implementation for current CPU load percentage
            if (function_exists('shell_exec')) {
                $psCommand = 'powershell -NoProfile -Command "Get-CimInstance -ClassName Win32_PerfFormattedData_PerfOS_Processor | Where-Object { $_.Name -eq \'_Total\' } | Select-Object -ExpandProperty PercentProcessorTime"';
                $psOutput = @shell_exec($psCommand);

                if (is_numeric(trim($psOutput))) {
                    $cpuUsagePercent = round(floatval(trim($psOutput)), 2);
                }
            }
            
            // Fallback to WMIC if PowerShell failed
            if ($cpuUsagePercent === null && function_exists('exec')) {
                $output = [];
                @exec('wmic cpu get loadpercentage /Value', $output);
                foreach ($output as $line) {
                    if (strpos($line, '=') !== false) {
                        $parts = explode('=', $line);
                        if (isset($parts[1]) && is_numeric(trim($parts[1]))) {
                            $cpuUsagePercent = round(floatval(trim($parts[1])), 2);
                            break;
                        }
                    }
                }
            }
        } else {
            $os = 'linux';
            // Linux implementation for load average
            if (function_exists('sys_getloadavg')) {
                $loadAverage = sys_getloadavg();
            }
        }

        return response()->json([
            'os' => $os,
            'load_average' => $loadAverage, // [1-min, 5-min, 15-min] for Linux
            'cpu_usage_percent' => $cpuUsagePercent, // Current usage for Windows
        ]);
    }

    /**
     * Get system memory usage (Total and Available).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSystemMemory()
    {
        $totalMemory = 0;
        $availableMemory = 0;

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows implementation
            
            // Try PowerShell first (more reliable on modern Windows)
            if (function_exists('shell_exec')) {
                $psCommand = 'powershell -NoProfile -Command "Get-CimInstance -ClassName Win32_OperatingSystem | Select-Object TotalVisibleMemorySize, FreePhysicalMemory | ConvertTo-Json"';
                $psOutput = @shell_exec($psCommand);
                
                if ($psOutput) {
                    $data = json_decode($psOutput, true);
                    if (isset($data['TotalVisibleMemorySize'])) {
                        $totalMemory = (intval($data['TotalVisibleMemorySize']) * 1024);
                        $availableMemory = (intval($data['FreePhysicalMemory']) * 1024);
                    }
                }
            }

            // Fallback to WMIC if PowerShell failed or returned 0
            if ($totalMemory == 0 && function_exists('exec')) {
                $output = [];
                @exec('wmic OS get FreePhysicalMemory,TotalVisibleMemorySize /Value', $output);
                
                foreach ($output as $line) {
                    if (trim($line) == '') continue;
                    if (strpos($line, 'TotalVisibleMemorySize') !== false) {
                        $parts = explode('=', $line);
                        if (isset($parts[1])) $totalMemory = (intval(trim($parts[1])) * 1024);
                    }
                    if (strpos($line, 'FreePhysicalMemory') !== false) {
                        $parts = explode('=', $line);
                        if (isset($parts[1])) $availableMemory = (intval(trim($parts[1])) * 1024);
                    }
                }
            }
        } else {
            // Linux implementation
            if (is_readable("/proc/meminfo")) {
                $stats = @file_get_contents("/proc/meminfo");
                if ($stats !== false) {
                    $lines = explode("\n", $stats);
                    foreach ($lines as $line) {
                        if (preg_match('/^MemTotal:\s+(\d+)\s+kB$/i', $line, $matches)) {
                            $totalMemory = $matches[1] * 1024;
                        } elseif (preg_match('/^MemAvailable:\s+(\d+)\s+kB$/i', $line, $matches)) {
                            $availableMemory = $matches[1] * 1024;
                        }
                    }
                    
                    // Fallback if MemAvailable is not present (older kernels)
                    if ($availableMemory == 0 && $totalMemory > 0) {
                         $memFree = 0;
                         $buffers = 0;
                         $cached = 0;
                         foreach ($lines as $line) {
                            if (preg_match('/^MemFree:\s+(\d+)\s+kB$/i', $line, $matches)) $memFree = $matches[1] * 1024;
                            if (preg_match('/^Buffers:\s+(\d+)\s+kB$/i', $line, $matches)) $buffers = $matches[1] * 1024;
                            if (preg_match('/^Cached:\s+(\d+)\s+kB$/i', $line, $matches)) $cached = $matches[1] * 1024;
                         }
                         $availableMemory = $memFree + $buffers + $cached;
                    }
                }
            }
        }

        return response()->json([
            'total_memory_bytes' => $totalMemory,
            'available_memory_bytes' => $availableMemory,
            'used_memory_bytes' => $totalMemory - $availableMemory,
            'percentage_used' => $totalMemory > 0 ? round((($totalMemory - $availableMemory) / $totalMemory) * 100, 2) : 0
        ]);
    }
}