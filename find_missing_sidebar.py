import os
root = r'.'
search1 = "include($_SERVER['DOCUMENT_ROOT'] . '/HR/includes/sidebar.php')"
search2 = 'include($_SERVER["DOCUMENT_ROOT"] . "/HR/includes/sidebar.php")'
missing = []
for dirpath, dirnames, filenames in os.walk(root):
    if os.path.basename(dirpath).startswith('module'):
        for fn in filenames:
            if fn.endswith('.php'):
                path = os.path.join(dirpath, fn)
                with open(path, 'r', encoding='utf-8', errors='ignore') as f:
                    txt = f.read()
                if search1 not in txt and search2 not in txt:
                    if fn not in ('index.php', 'dashboard.php', 'sidebar.php'):
                        missing.append(path.replace('\\', '/'))
print('MISSING_COUNT', len(missing))
for m in missing:
    print(m)
