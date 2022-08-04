const { series, src, dest, watch } = require("gulp");
const clean = require("gulp-clean");
const zip = require("gulp-zip");
const exec = require("util").promisify(require("child_process").exec);
const cfg = require("./gulpfile.json");
const rename = require("gulp-rename");
const replace = require("gulp-replace");

/**
 * watch for changes
 * @return FSWatcher
 */
exports.watcher = function () {
  watch(["./modules/widgets/keysystems_account.php"], exports.generate);
};

/**
 * Perform PHP Linting
 */
async function doLint() {
  // these may fail, it's fine
  try {
    await exec(`${cfg.phpcsfixcmd} ${cfg.phpcsparams}`);
  } catch (e) {}
  // these shouldn't fail
  try {
    await exec(`${cfg.phpcschkcmd} ${cfg.phpcsparams}`);
  } catch (e) {
    await Promise.reject(e.message);
  }
  await Promise.resolve();
}

/**
 * cleanup old build folder / archive
 * @return stream
 */
function doDistClean() {
  return src([cfg.archiveBuildPath, "whmcs-*-widget-latest.zip"], {
    read: false,
    base: ".",
    allowEmpty: true,
  }).pipe(
    clean({
      force: true,
    })
  );
}
/**
 * Clean up files
 * @return stream
 */
function doFullClean() {
  return src(cfg.filesForCleanup, {
    read: false,
    base: ".",
    allowEmpty: true,
  }).pipe(
    clean({
      force: true,
    })
  );
}

/**
 * helper function for creating the registrar-specific archive
 * @param string registrar
 * @returns stream
 */
function doArchiveRegistrar(registrar) {
  const filelist = cfg.filesForArchive;
  filelist.push(`./${cfg.archiveBuildPath}/**/!(${registrar}).php`);
  return src(filelist)
    .pipe(zip(`whmcs-${registrar}-widget-account.zip`))
    .pipe(dest("./pkg"));
}

/**
 * build zip archive
 * @return stream
 */
function doArchives() {
  const filelist = cfg.filesForArchive;
  filelist.push(`./${cfg.archiveBuildPath}/**/!(keysystems).php`);
  return doArchiveRegistrar("ispapi"), doArchiveRegistrar("keysystems");
}

/**
 * copy keysystems to ispapi
 * @return stream
 */
function copyKeysystemsFile() {
  return src("modules/widgets/keysystems_account.php", { base: "." })
    .pipe(rename("ispapi_account.php"))
    .pipe(dest("modules/widgets/", { base: "." }));
}

/**
 * replace specified strings (keysystems to ispapi)
 * @return stream
 */
function replaceCodeBase() {
  return src("./modules/widgets/ispapi_account.php")
    .pipe(replace("RRPproxy (Keysystems)", "HEXONET (Ispapi)"))
    .pipe(replace("RRPproxy", "HEXONET"))
    .pipe(
      replace(
        "https://github.com/rrpproxy/whmcs-rrpproxy-registrar/raw/master/modules/registrars/keysystems/logo.png",
        "https://github.com/hexonet/whmcs-ispapi-registrar/raw/master/modules/registrars/ispapi/logo.png"
      )
    )
    .pipe(
      replace(
        "https://github.com/rrpproxy/whmcs-rrpproxy-registrar",
        "https://github.com/hexonet/whmcs-ispapi-registrar"
      )
    )
    .pipe(replace("KEYSYSTEMS", "ISPAPI"))
    .pipe(replace("KeySystems", "Ispapi"))
    .pipe(replace("Keysystems", "Ispapi"))
    .pipe(replace("keysystems", "ispapi"))
    .pipe(dest("./modules/widgets/"));
}

exports.lint = series(doLint);
exports.generate = series(copyKeysystemsFile, replaceCodeBase);
exports.archives = series(exports.generate, doArchives);
exports.release = series(doDistClean, exports.archives, doFullClean);
