import { spawnSync } from 'node:child_process';
import { existsSync } from 'node:fs';

/**
 * Release gate.
 * Python check-site.py is a legacy/manual smoke helper — NOT part of this gate.
 */

const isWindows = process.platform === 'win32';
const dockerPath = isWindows
  ? 'C:\\Program Files\\Docker\\Docker\\resources\\bin\\docker.exe'
  : 'docker';

function run(command, args, options = {}) {
  const label = [command, ...args].join(' ');
  console.log(`\n[preflight] ${label}`);
  const result = spawnSync(command, args, {
    stdio: 'inherit',
    shell: false,
    ...options,
  });
  if (result.error) {
    console.error(`[preflight] failed to start: ${result.error.message}`);
    process.exit(1);
  }
  if (result.status !== 0) {
    console.error(`[preflight] failed: ${label}`);
    process.exit(result.status ?? 1);
  }
}

function dockerCompose(args) {
  if (isWindows && !existsSync(dockerPath)) {
    console.error(`[preflight] Docker CLI not found: ${dockerPath}`);
    process.exit(1);
  }
  run(dockerPath, ['compose', ...args]);
}

function nodeCommand(command, args) {
  if (isWindows) {
    run('cmd.exe', ['/c', command, ...args]);
    return;
  }
  run(command, args);
}

dockerCompose(['exec', '-T', 'php', 'php', 'scripts/migrate-db.php']);
dockerCompose(['exec', '-T', 'php', 'php', 'tests/run.php']);
nodeCommand('npm', ['run', 'build']);
nodeCommand('npx', ['playwright', 'test']);

console.log('\n[preflight] OK');
