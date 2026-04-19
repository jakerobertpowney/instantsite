import { readFileSync, writeFileSync } from 'node:fs';
import path from 'node:path';

const root = process.cwd();

const patches = [
    {
        file: 'resources/js/routes/dashboard/index.ts',
        replacements: [["import site from './site'", "import siteGroup from './site'"]],
        objectPatch: (contents) => contents.replace('\n    site,\n', '\n    site: siteGroup,\n'),
    },
    {
        file: 'resources/js/routes/password/index.ts',
        replacements: [["import confirm from './confirm'", "import confirmGroup from './confirm'"]],
        objectPatch: (contents) => contents.replace('\n    confirm,\n', '\n    confirm: confirmGroup,\n'),
    },
    {
        file: 'resources/js/routes/search/index.ts',
        replacements: [["import discover from './discover'", "import discoverGroup from './discover'"]],
        objectPatch: (contents) => contents.replace('\n    discover,\n', '\n    discover: discoverGroup,\n'),
    },
];

for (const patch of patches) {
    const filePath = path.join(root, patch.file);
    let contents = readFileSync(filePath, 'utf8');

    for (const [search, replace] of patch.replacements) {
        contents = contents.replace(search, replace);
    }

    if (patch.objectPatch) {
        contents = patch.objectPatch(contents);
    }

    writeFileSync(filePath, contents);
}
