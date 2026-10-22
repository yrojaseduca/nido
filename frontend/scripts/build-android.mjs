import { existsSync, readFileSync } from 'node:fs';
import { homedir } from 'node:os';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { spawnSync } from 'node:child_process';

const frontend = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const variant = process.argv[2];
const device = process.argv[3] || '';
if (!['debug', 'release'].includes(variant)) {
    throw new Error('Expected debug or release.');
}
if (!['', 'standard', 'nquire500'].includes(device)) {
    throw new Error('Expected an optional standard or nquire500 device target.');
}
if (variant === 'release' && !existsSync(resolve(frontend, 'android/signing.properties'))) {
    throw new Error('Falta android/signing.properties. Consulta ANDROID.md para configurar la firma release.');
}
const gradleFile = readFileSync(resolve(frontend, 'android/app/build.gradle'), 'utf8');
const versionMatch = gradleFile.match(/versionName\s+"([^"]+)"/);
const apkVersion = versionMatch?.[1] || '2.1';
const repository = resolve(frontend, '..');
const gitShaResult = spawnSync('git', ['rev-parse', '--short', 'HEAD'], {
    cwd: repository,
    encoding: 'utf8',
});
const buildSha = process.env.VITE_BUILD_SHA
    || gitShaResult.stdout?.trim()
    || '__BUILD_SHA__';
const buildDate = process.env.VITE_BUILD_DATE
    || new Date().toISOString().replace('T', ' ').replace(/\.\d{3}Z$/, ' UTC');
const env = {
    ...process.env,
    JAVA_HOME: process.env.JAVA_HOME || resolve(homedir(), '.local/lib/jvm/temurin-21'),
    VITE_ALLOW_HTTP: variant === 'debug' ? 'true' : 'false',
    VITE_APK_VERSION: process.env.VITE_APK_VERSION || `${apkVersion}${device === 'nquire500' ? '-nquire500' : ''}`,
    VITE_BUILD_SHA: buildSha,
    VITE_BUILD_DATE: buildDate,
};
// A release without an explicit default must not inherit a laboratory URL from
// Vite dotenv files. Runtime setup will request the server on first launch.
if (variant === 'release') {
    env.VITE_API_BASE = process.env.VITE_API_BASE?.trim() || '';
    if (env.VITE_API_BASE) {
        const url = new URL(env.VITE_API_BASE);
        if (url.protocol !== 'https:' || url.username || url.password || url.search || url.hash
            || !['', '/api'].includes(url.pathname.replace(/\/+$/, ''))) {
            throw new Error('La URL predeterminada de release debe ser HTTPS y terminar en /api.');
        }
    }
}

function run(command, args, cwd) {
    const result = spawnSync(command, args, { cwd, env, stdio: 'inherit' });
    if (result.error) {
        throw result.error;
    }
    if (result.status !== 0) {
        process.exit(result.status || 1);
    }
}

run('npm', ['run', 'android:sync'], frontend);
const buildDevice = device || 'standard';
const gradleTask = `assemble${buildDevice[0].toUpperCase()}${buildDevice.slice(1)}${variant[0].toUpperCase()}${variant.slice(1)}`;
run('./gradlew', [gradleTask], resolve(frontend, 'android'));
