<?php

class CRM_Utils_Shell {
  static function run($command, $options = array()) {
    $options['throw_exception_on_nonzero'] = CRM_Utils_Array::fetch('throw_exception_on_nonzero', $options, TRUE);
    $options['print_command'] = CRM_Utils_Array::fetch('print_command', $options, FALSE);
    $options['input'] = CRM_Utils_Array::fetch('input', $options);
    if ($options['print_command']) {
      print("$command\n");
    }
    $descriptors_spec = array (
      0 => array('pipe', 'r'),
      1 => array('pipe', 'w'),
      2 => array('pipe', 'w'),
    );
    $process = proc_open($command, $descriptors_spec, $pipes);
    if ($process === FALSE) {
      throw new \Exception("Unable to proc_open($command).");
    } 
    stream_set_blocking($pipes[1], FALSE);
    if ($options['input']) {
      fwrite($pipes[0], $options['input']);
      fclose($pipes[0]);
    }
    $stdout_data = '';
    $stderr_data = '';
    $stdout_done = FALSE;
    $stderr_done = FALSE;
    while (!$stdout_done && !$stderr_done) {
      $read_streams = array($pipes[1], $pipes[2]);
      $write_streams = null;
      $exceptions = null;
      $result = stream_select($read_streams, $write_streams, $exceptions, 5);
      if ($result === FALSE) {
        throw new \Exception("Error running stream_select on pipe.");
      }
      if ($result > 0) { 
        foreach ($read_streams as $read_stream) {
          if ($read_stream == $pipes[1]) {
            $result = fgets($read_stream);
            if ($result === FALSE) {
              if (!feof($read_stream)) {
                throw new \Exception("Error reading from proc_open($command) stderr stream.");
              } else {
                $stdout_done = TRUE;
              }
            } else {
              $stdout_data .= $result;
            }
          } elseif ($read_stream == $pipes[2]) {
            $result = fgets($read_stream);
            if ($result === FALSE) {
              if (!feof($read_stream)) {
                throw new \Exception("Error reading from proc_open($command) stderr stream.");
              } else {
                $stderr_done = TRUE;
              }
            } else {
              $stderr_data .= $result;
            }
          }
        }
      } else {
        break;
      }
    }
    fclose($pipes[1]);
    fclose($pipes[2]);
    $return_value = proc_close($process);
    if ($return_value != 0 && $options['throw_exception_on_nonzero']) {
      throw new \Exception("Error running '$command'. Non-zero return value ($return_value)\nstdout:\n$stdout_data\nstderr\n$stderr_data");
    }
    return array($stdout_data, $stderr_data);
  }
}
