import {
    useState,
    useEffect,
    useMemo,
    useRef,
    useCallback,
} from '@wordpress/element';
import {
    Card,
    TextControl,
    ToggleControl,
    SelectControl,
    TabPanel,
    Button,
    Notice,
    Modal,
    Spinner,
    Tooltip
} from '@wordpress/components';

const responsiveGroupStyle = {
    display: 'flex',
    flexWrap: 'wrap',
    gap: '32px',
    marginTop: '24px',
};

const groupTitleStyle = {
    flex: '1 1 300px',
    minWidth: '250px',
};

const groupCardStyle = {
    flex: '1 1 550px',
    minWidth: '300px',
    padding: 20,
};

const GROUP_LABELS = {
    general: 'General',
    account: 'Account Settings',
    shopping_experience: 'Shopping Experience',
    payment_methods: 'Payment Methods',
    shopping_features: 'WooCommerce Subscriptions',
    advanced: 'Advanced Settings',
};

const SECTION_DESCRIPTIONS = {
    general: (
        <>
            <p>Enable or disable Paysafe payments.</p>
            <p>If you enable test mode, the extension will connect to the Test environment. In this case, you must use test cards provided in the Paysafe documentation.</p>
        </>
    ),
    account: (
        <>
            <p>Contact our <a href="https://portals.paysafe.com/helpcenter/dashboard">Integrations team</a> to get your Business Portal access and help setting up.</p>
            <p>When you have access to the Business Portal, you can use it to manage all of your accounts and API keys.</p>
            <p>You need two API keys:</p>
            <ul>
                <li>Public key for browser-to-Paysafe communications.</li>
                <li>Private key for server-to-server calls.</li>
            </ul>
            <p>
                <a href="https://portals.paysafe.com/login" target="_blank" rel="noreferrer">
                    Visit our Merchant Portal for more details ↗
                </a>
            </p>
        </>
    ),
    shopping_experience: (<p>Basic settings related to the configuration of the Paysafe Checkout.</p>),
    payment_methods: (
        <>
            <p>Provide your customers with different payment methods that suit their needs.</p>
            <p>You can select only the payment methods that you have configured with Paysafe.</p>
            <p>If you'd like to enable additional methods, please contact customer support.</p>
        </>
    ),
    shopping_features: (<p>Enable or disable WooCommerce subscriptions support.</p>),
    advanced: (<p>Logging settings let you enable debug logs and choose whether to mask sensitive user data.</p>),
};

// key shapes handled:
//   payment_method_USD_CARD
//   payment_method_USD_CARD_sandbox
//   payment_method_USD_CARD_live
const parsePaymentKey = (key) => {
    const m = key.match(/^payment_method_([A-Z]{3})_([A-Z0-9]+)(?:_(sandbox|live))?$/i);
    if (!m) return { currency: null, method: null, env: null };
    return { currency: m[1], method: m[2], env: m[3] || null };
};

function stableHash(obj) {
    // Deterministic hash (order-insensitive for keys)
    // Good enough to detect changes for beforeunload.
    try {
        const replacer = (_, value) =>
            value && typeof value === 'object' && !Array.isArray(value)
                ? Object.keys(value).sort().reduce((acc, k) => { acc[k] = value[k]; return acc; }, {})
                : value;
        return JSON.stringify(obj, replacer);
    } catch {
        return String(Math.random());
    }
}

export default function SettingsApp() {
    const [fields, setFields] = useState({});
    const [form, setForm] = useState({});
    const [status, setStatus] = useState('');
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(true);
    const [fadeOut, setFadeOut] = useState(false);
    const [testingConnection, setTestingConnection] = useState(false);
    const [activeTab, setActiveTab] = useState(null);
    const [activeMethodModal, setActiveMethodModal] = useState(null);
    const [showCredentialModal, setShowCredentialModal] = useState(false);
    const [expandedKeys, setExpandedKeys] = useState({});
    const [testConnectionNotice, setTestConnectionNotice] = useState(null);
    const [originalKeyFields, setOriginalKeyFields] = useState(null);
    const [testConnectionSucceeded, setTestConnectionSucceeded] = useState(false);
    const [modalKeyFields, setModalKeyFields] = useState(null);
    const MODAL_ONLY_GROUPS = ['card', 'neteller', 'applepay', 'googlepay'];
    const [revealedKeys, setRevealedKeys] = useState({});
    const [testConnectionFullFields, setTestConnectionFullFields] = useState(null);
    const [pendingPMTreesByEnv, setPendingPMTreesByEnv]   = useState({ sandbox: null, live: null });
    const [fieldByKey, setFieldByKey] = useState({});

    const env = activeTab === 'live' ? 'live' : 'sandbox';

    const [isSaving, setIsSaving] = useState(false);

    // Baseline snapshot of the last-saved form; compared to current `form`
    const baselineRef = useRef(null);

    // resolve the field objects for the active env
    const pubKeyId  = activeTab === 'sandbox' ? 'test_public_api_key'  : 'live_public_api_key';
    const privKeyId = activeTab === 'sandbox' ? 'test_private_api_key' : 'live_private_api_key';
    const pubKeyField  = Object.values(fields).flat().find(f => f.key === pubKeyId);
    const privKeyField = Object.values(fields).flat().find(f => f.key === privKeyId);

    const [copyStatus, setCopyStatus] = useState(null); // { key, ok } | null

    // Small helper: turn HTML (with <br>, etc.) into plain text for the clipboard
    const htmlToPlainText = (html) => {
        const el = document.createElement('div');
        el.innerHTML = html || '';
        return (el.textContent || el.innerText || '').trim();
    };


    // Derived dirty flag
    const isDirty = useMemo(() => {
        if (!baselineRef.current) return false;
        return stableHash(form) !== baselineRef.current;
    }, [form]);

    useEffect(() => {
        const handler = (e) => {
            if (!isDirty && !isSaving) return;
            e.preventDefault();
            e.returnValue = ''; // Required for Chrome
        };
        window.addEventListener('beforeunload', handler);
        return () => window.removeEventListener('beforeunload', handler);
    }, [isDirty, isSaving]);

    useEffect(() => {
        fetch(PaysafeSettingsData.apiUrl, {
            headers: { 'X-WP-Nonce': PaysafeSettingsData.nonce }
        })
            .then(res => res.json())
            .then(data => {
                const initial = {};
                const grouped = {
                    general: [],
                    account: [],
                    payment_methods: [],
                    shopping_experience: [],
                    shopping_features: [],
                    advanced: [],
                    card: [],
                    neteller: [],
                    applepay: [],
                    googlepay: [],
                };

                // Flat lookup of all fields (even those without a group)
                const byKey = {};
                data.forEach((f) => { byKey[f.key] = f; });
                setFieldByKey(byKey);

                data.forEach((f) => {
                    const normalized = (f.group || '').toLowerCase();
                    if (!normalized || !grouped[normalized]) return;

                    initial[f.key] = f.value ?? f.default ?? '';

                    // Payment rows (toggles + account selects) always live under the main bucket
                    let targetGroup = normalized;
                    if (f.key.startsWith('payment_method_') || f.key.startsWith('account_id_')) {
                        targetGroup = 'payment_methods';
                    }

                    grouped[targetGroup].push({ ...f, group: normalized });
                });

                setForm(initial);
                setFields(grouped);

                // establish baseline AFTER initial load so the page isn't "dirty"
                baselineRef.current = stableHash(initial);

                // Set activeTab based on test_mode
                const mode = initial.test_mode === 'yes' ? 'sandbox' : 'live';
                setActiveTab(mode);
                setFadeOut(true);
                setTimeout(() => setLoading(false), 300);
            })
            .catch(() => {
                setError('Failed to load settings.');
                setLoading(false);
            });
    }, []);

    const handleChange = (key, value) => {
        setForm(prev => {
            const updated = { ...prev, [key]: value };

            // Sync tabs when test_mode changes
            if (key === 'test_mode') {
                setActiveTab(value === 'yes' ? 'sandbox' : 'live');
            }

            return updated;
        });
    };

    const handleSubmit = useCallback((e) => {
        e.preventDefault();
        setStatus('Saving...');
        setIsSaving(true);

        const body = {
            ...form,
            ...(pendingPMTreesByEnv.sandbox ? { payment_methods_sandbox: pendingPMTreesByEnv.sandbox } : {}),
            ...(pendingPMTreesByEnv.live    ? { payment_methods_live:    pendingPMTreesByEnv.live    } : {}),
        };

        fetch(PaysafeSettingsData.apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': PaysafeSettingsData.nonce },
            body: JSON.stringify(body),
        })
            .then((res) => {
                if (!res.ok) throw new Error('Save failed');
                return res.json();
            })
            .then(() => {
                // ✅ success: re-baseline so the page is no longer “dirty”
                baselineRef.current = stableHash(form);

                // Clear any global beforeunload hooks
                window.onbeforeunload = null;

                // Some WP/Woo pages attach namespaced handlers; safe to try removing
                if (window.jQuery) {
                    try { window.jQuery(window).off('beforeunload.wc-settings'); } catch {}
                    try { window.jQuery(window).off('beforeunload.edit-post'); } catch {}
                }

                setStatus('Settings saved!');
                // Optional: clear the status after a short delay
                setTimeout(() => setStatus(''), 2500);
            })
            .catch(() => {
                setStatus('Save failed.');
            })
            .finally(() => {
                setIsSaving(false);
            });
    }, [form, pendingPMTreesByEnv]);

    const renderField = (id) => {
        const field = Object.values(fields).flat().find(f => f.key === id);
        if (!field) return null;

        const value = form[id];
        const commonProps = {
            label: field.title || field.label,
            value: value ?? '',
            onChange: (v) => handleChange(id, v)
        };
        
        if (field.type === 'checkbox') {
            return (
                <Tooltip text={field.description}>
                    <div
                        style={{
                            display: 'flex',
                            alignItems: 'flex-start',
                            gap: '12px',
                            padding: '8px',
                            borderRadius: '4px',
                            flexWrap: 'wrap'
                        }}
                        tabIndex={0}
                        role="group"
                        aria-label={field.description}
                    >
                        <ToggleControl
                            checked={value === 'yes'}
                            onChange={(v) => handleChange(id, v ? 'yes' : 'no')}
                            aria-label={field.title}
                        />
                        <div style={{ flex: 1 }}>
                            <div style={{ fontWeight: 'bold' }}>{field.title}</div>
                            <div style={{ color: '#666', fontSize: '13px', marginTop: 4 }}>
                                {field.label}
                            </div>
                        </div>
                    </div>
                </Tooltip>
            );
        }

        if (field.type === 'select' || field.type === 'paysafe_select') {
            const options = Object.entries(field.options || {}).map(([val, label]) => ({
                label,
                value: val
            }));

            // If only one option, show disabled TextControl
            if (options.length === 1) {
                return (
                    <TextControl
                        className="paysafe-text-static"
                        label={field.title || field.label}
                        value={options[0].label}
                        disabled
                    />
                );
            }

            return (
                <SelectControl
                    {...commonProps}
                    options={options}
                />
            );
        }


        return <TextControl {...commonProps} />;
    };

    const toggleKeyVisibility = (keyName) => {
        setExpandedKeys(prev => ({
            ...prev,
            [keyName]: !prev[keyName],
        }));
    };

    const renderApiKey = (value, keyName) => {
        const isExpanded = expandedKeys[keyName];
        if (!value) return null;

        const shortValue = value.slice(0, 80);

        return (
            <div>
                {isExpanded ? (
                    <>
                        <div className="paysafe-api-key-wrapper">{value}</div>
                        <Button
                            variant="link"
                            onClick={() => toggleKeyVisibility(keyName)}
                            className="paysafe-show-toggle"
                        >
                            Show less
                        </Button>
                    </>
                ) : (
                    <div className="paysafe-api-key-wrapper">
                        {shortValue}
                        <span style={{ opacity: 0.6 }}>...</span>{' '}
                        <Button
                            variant="link"
                            onClick={() => toggleKeyVisibility(keyName)}
                            className="paysafe-show-toggle"
                            style={{
                                display: 'inline',
                                padding: 0,
                                fontSize: '13px',
                                marginLeft: '4px',
                            }}
                        >
                            Show more
                        </Button>
                    </div>
                )}
            </div>
        );
    };


    const renderCredentialSummary = () => (
        <TabPanel
            key={`account-${activeTab}`}
            className="components-tab-panel"
            activeClass="is-active"
            tabs={[
                { name: 'sandbox', title: 'Sandbox' },
                { name: 'live', title: 'Live' },
            ]}
            onSelect={(tabName) => setActiveTab(tabName)}
            initialTabName={activeTab}
        >
            {(tab) => {
                const isSandbox = tab.name === 'sandbox';
                const pubKey = isSandbox ? form.test_public_api_key : form.live_public_api_key;
                const privKey = isSandbox ? form.test_private_api_key : form.live_private_api_key;

                return (
                    <div style={{ marginTop: '16px' }}>
                        {(pubKey && privKey) ? (
                            <>
                                <div style={{marginBottom: '12px'}}>
                                    <strong style={{display: 'block', marginBottom: '4px'}}>Public API Key</strong>
                                    {renderApiKey(pubKey, 'public')}
                                </div>

                                <div>
                                    <strong style={{display: 'block', marginBottom: '4px'}}>Private API Key</strong>
                                    {renderApiKey(privKey, 'private')}
                                </div>
                            </>
                        ) :
                            <Notice status="warning" isDismissible={false}>
                                Please configure your API Keys!
                            </Notice>
                        }
                    </div>
                );

            }}
        </TabPanel>
    );

    const openCredentialModal = () => {
        // Store current API key values
        const savedKeys = {
            test_public_api_key: form.test_public_api_key,
            test_private_api_key: form.test_private_api_key,
            live_public_api_key: form.live_public_api_key,
            live_private_api_key: form.live_private_api_key,
        };

        setOriginalKeyFields(savedKeys);
        setModalKeyFields(savedKeys);
        setShowCredentialModal(true);
    };

    const closeCredentialModal = () => {
        setShowCredentialModal(false);

        const env = activeTab === 'sandbox' ? 'sandbox' : 'live';
        const envSuffix = `_${env}`;

        if (testConnectionSucceeded && modalKeyFields) {
            // 1) Start from current form + the keys entered in the modal
            const newForm = { ...form, ...modalKeyFields };

            // 2) If backend sent the flattened full list (defaults), rebuild active-env rows & modal fields
            if (Array.isArray(testConnectionFullFields) && testConnectionFullFields.length) {
                // Collect only the active-env slices we need to replace
                const regroupedNew = {
                    payment_methods: [],
                    card: [],
                    neteller: [],
                    applepay: [],
                    googlepay: [],
                };

                testConnectionFullFields.forEach((f) => {
                    if (!f || !f.key) return;
                    const k = String(f.key);

                    // Only apply fields for the currently active environment
                    if (!k.endsWith(envSuffix)) return;

                    // Prefer default when available (we want fresh defaults after a successful test)
                    const v = (f.default !== undefined ? f.default : f.value);
                    newForm[k] = v ?? '';

                    // Route into the correct group for rendering
                    if (k.startsWith('payment_method_') || k.startsWith('account_id_')) {
                        regroupedNew.payment_methods.push(f);
                    } else {
                        const g = (f.group || '').toLowerCase();
                        if (g && Object.prototype.hasOwnProperty.call(regroupedNew, g)) {
                            regroupedNew[g].push(f);
                        }
                    }
                });

                // Replace only the subsets for the active env in each group, keep the other env's rows intact
                setFields((prev) => {
                    const replaceEnvSubset = (prevArr = [], newArr = []) => {
                        const keepOtherEnv = prevArr.filter((it) => !String(it?.key || '').endsWith(envSuffix));
                        return [...keepOtherEnv, ...newArr];
                    };

                    return {
                        ...prev,
                        payment_methods: replaceEnvSubset(prev.payment_methods, regroupedNew.payment_methods),
                        card:       replaceEnvSubset(prev.card,       regroupedNew.card),
                        neteller:   replaceEnvSubset(prev.neteller,   regroupedNew.neteller),
                        applepay:   replaceEnvSubset(prev.applepay,   regroupedNew.applepay),
                        googlepay:  replaceEnvSubset(prev.googlepay,  regroupedNew.googlepay),
                    };
                });
            }

            // 3) Persist the canonical tree for this env so Save Settings writes it to the DB
            if (pendingPMTreesByEnv?.[env]) {
                newForm[`payment_methods_${env}`] = pendingPMTreesByEnv[env];
            }

            // 4) Commit hydrated form
            setForm(newForm);

        } else if (originalKeyFields) {
            // Revert keys if test didn't succeed (or wasn't run)
            setForm((prev) => ({ ...prev, ...originalKeyFields }));
        }

        // 5) Cleanup temporary state so next open starts fresh
        setOriginalKeyFields(null);
        setPendingPMTreesByEnv({ sandbox: null, live: null });
        setTestConnectionFullFields(null);
        setTestConnectionSucceeded(false);
        setTestConnectionNotice(null);
        setModalKeyFields(null);
        setRevealedKeys({});
    };

    const handleTestConnection = () => {
        setTestingConnection(true);

        fetch(PaysafeSettingsData.apiUrl.replace('/settings', '/test-connection'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': PaysafeSettingsData.nonce
            },
            body: JSON.stringify({
                ...form,
                ...(modalKeyFields || {}),
                test_mode: activeTab === 'sandbox' ? 'yes' : 'no'
            })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    setTestConnectionSucceeded(true);
                    setTestConnectionNotice({
                        status: 'success',
                        message: 'You successfully applied your API credentials. You can now close this dialog.',
                    });

                    // 1) Save the canonical payment_methods tree for this env (for POST / save)
                    const tree = data?.newSettings?.[`payment_methods_${env}`];
                    if (tree) {
                        setPendingPMTreesByEnv((prev) => ({ ...prev, [env]: tree }));
                    }

                    // 2) Keep a single source of truth to rebuild *all* fields (both envs) on modal close
                    if (Array.isArray(data.full_fields_defaults)) {
                        setTestConnectionFullFields(data.full_fields_defaults);
                        const byKey2 = {};
                        data.full_fields_defaults.forEach((f) => { byKey2[f.key] = f; });
                        setFieldByKey(byKey2);
                    } else if (Array.isArray(data.full_fields)) {
                        setTestConnectionFullFields(data.full_fields);
                        const byKey2 = {};
                        data.full_fields.forEach((f) => { byKey2[f.key] = f; });
                        setFieldByKey(byKey2);
                    }

                } else {
                    setTestConnectionNotice({
                        status: 'error',
                        message: data.message || 'An unknown error occurred.',
                    });
                }
            })
            .catch(() => {
                setTestConnectionNotice({
                    status: 'error',
                    message: 'An error occurred while testing the connection.',
                });
            })
            .finally(() => setTestingConnection(false));
    };

    const toggleKeyReveal = (keyName) => {
        setRevealedKeys(prev => ({
            ...prev,
            [keyName]: !prev[keyName]
        }));
    };


    if (error) return <Notice status="error">{error}</Notice>;

    if (loading) {
        return (
            <div className={`paysafe-spinner-wrapper ${fadeOut ? 'fade-out' : ''}`}
                 style={{ textAlign: 'center', marginTop: '80px' }}>
                <Spinner />
                <p style={{ marginTop: '16px' }}>Loading settings…</p>
            </div>
        );
    }

    return (
        <form onSubmit={handleSubmit} style={{ maxWidth: 1200, margin: '0 auto' }}>
            <h1>Paysafe Checkout Settings</h1>

            {Object.entries(fields)
                .filter(([groupKey]) => !MODAL_ONLY_GROUPS.includes(groupKey))
                .map(([groupKey, groupFields]) => {
                    const showCredentialTabs = groupKey === 'account';
                    const showSandboxNotice = groupKey === 'general' && form.test_mode === 'yes';

                    // Special handling for missing subscriptions
                    let subscriptionNotice = null;
                    if (groupKey === 'shopping_features') {
                        const missingSub = Object.values(fields)
                            .flat()
                            .find((f) => f.key === 'subscriptions_title_missing');
                        if (missingSub) {
                            subscriptionNotice = missingSub.description;
                        }
                    }

                    return (
                        <div key={groupKey} style={responsiveGroupStyle}>
                            <div style={groupTitleStyle}>
                                <h2>{GROUP_LABELS[groupKey]}</h2>
                                {SECTION_DESCRIPTIONS[groupKey]}
                            </div>

                            <Card style={groupCardStyle}>
                                {showCredentialTabs && renderCredentialSummary()}

                                {showCredentialTabs && (
                                    <Button
                                        variant="primary"
                                        onClick={openCredentialModal}
                                        style={{ marginTop: '16px' }}
                                    >
                                        Configure keys
                                    </Button>
                                )}

                                {groupKey === 'payment_methods' ? (
                                    <div style={{ display: 'flex', flexDirection: 'column' }}>
                                        {/* Top env tabs for payment methods */}
                                        <TabPanel
                                            key={`payment-${activeTab}`}
                                            className="paysafe-payment-tabs"
                                            activeClass="is-active"
                                            tabs={[
                                                { name: 'sandbox', title: 'Sandbox' },
                                                { name: 'live', title: 'Live' },
                                            ]}
                                            onSelect={(tabName) => setActiveTab(tabName)}
                                            initialTabName={activeTab}
                                        >
                                            {() => null}
                                        </TabPanel>

                                        {activeTab === 'sandbox' && (
                                            <div style={{ marginBottom: '8px', marginTop: '8px' }}>
                                                <Notice
                                                    status="warning"
                                                    isDismissible={false}
                                                >
                                                    You are configuring test payment methods.
                                                </Notice>
                                            </div>
                                        )}

                                        {groupFields
                                            // show only rows for the current env; accept unsuffixed keys as current env (b/c)
                                            .filter((f) => {
                                                if (!f.key.startsWith('payment_method_')) return false;
                                                const { env } = parsePaymentKey(f.key);
                                                return (env ?? activeTab) === activeTab;
                                            })
                                            .map((methodField) => {
                                                const { currency, method, env } = parsePaymentKey(methodField.key);
                                                const methodLower = (method || '').toLowerCase();

                                                // find the account field for the same env
                                                const accountKey = `account_id_${currency}_${method}_${env || activeTab}`;
                                                const accountField = groupFields.find((f) => f.key === accountKey);

                                                const isDisabled = !accountField;
                                                const enabled = form[methodField.key] === 'yes';
                                                const icon =
                                                    methodField.custom_attributes?.icon ||
                                                    `assets/img/paysafe-${methodLower}.png`;

                                                const groupItems = Object.values(fields).flat().find((f) => f.group === methodLower);
                                                const showMoreButton = ['card', 'applepay', 'googlepay', 'neteller'].includes(methodLower) && groupItems;

                                                return (
                                                    <div
                                                        key={methodField.key}
                                                        style={{
                                                            display: 'flex',
                                                            alignItems: 'flex-start',
                                                            padding: '12px 0',
                                                            borderBottom: '1px solid #e0e0e0',
                                                            gap: '16px',
                                                        }}
                                                    >
                                                        {/* Column 1: toggle or i-tooltip */}
                                                        <div
                                                            style={{
                                                                width: 48,
                                                                display: 'flex',
                                                                alignItems: 'center',
                                                                justifyContent: 'center',
                                                            }}
                                                        >
                                                            {isDisabled ? (
                                                                <Tooltip
                                                                    text={
                                                                        (activeTab === 'sandbox' && !form.test_public_api_key) ||
                                                                        (activeTab === 'live' && !form.live_public_api_key)
                                                                            ? 'Please configure your API keys'
                                                                            : 'Please enable it in your merchant portal'
                                                                    }
                                                                >
                                                                <span
                                                                    className="dashicons dashicons-info-outline"
                                                                    style={{ fontSize: 30, color: '#f0b400', cursor: 'help', lineHeight: 0.9, marginRight: 20 }}
                                                                    tabIndex={0}
                                                                    role="img"
                                                                />
                                                                </Tooltip>
                                                            ) : (
                                                                <ToggleControl
                                                                    label=""
                                                                    checked={enabled}
                                                                    onChange={(v) => handleChange(methodField.key, v ? 'yes' : 'no')}
                                                                    aria-label={`Enable ${methodField.title}`}
                                                                />
                                                            )}
                                                        </div>

                                                        {/* Column 2: icon/title/desc */}
                                                        <div style={{flex: 1}}>
                                                            <div style={{width: 40}}>
                                                                <img
                                                                    src={icon}
                                                                    alt={methodField.title}
                                                                    style={{
                                                                        height: 18
                                                                    }}
                                                                    onError={(e) => {
                                                                        e.currentTarget.style.display = 'none';
                                                                    }}
                                                                />
                                                            </div>
                                                            <div style={{fontSize: 13, color: '#666'}}>
                                                                {methodField.description}
                                                            </div>
                                                        </div>

                                                        {/* Column 3: account id select */}
                                                        {accountField && (
                                                            <div className="paysafe-account-select">
                                                                {renderField(accountField.key)}
                                                            </div>
                                                        )}

                                                        {/* Column 4: ... */}
                                                        {!isDisabled && (
                                                            <div style={{ width: 40, textAlign: 'center' }}>
                                                                {showMoreButton && (
                                                                    <Button
                                                                        icon="ellipsis"
                                                                        label="More options"
                                                                        onClick={() => setActiveMethodModal(methodLower)}
                                                                        style={{ cursor: 'pointer' }}
                                                                    />
                                                                )}
                                                            </div>
                                                        )}
                                                    </div>
                                                );
                                            })}
                                    </div>
                                ) : subscriptionNotice ? (
                                    //  Show notice if subscriptions missing
                                    <Notice status="warning" isDismissible={false}>
                                        <div dangerouslySetInnerHTML={{ __html: subscriptionNotice }} />
                                    </Notice>
                                ) : (
                                    // Non-payment groups: render normal fields (excluding api keys + payment keys)
                                    groupFields
                                        .filter(
                                            (f) =>
                                                ![
                                                    'test_public_api_key',
                                                    'test_private_api_key',
                                                    'live_public_api_key',
                                                    'live_private_api_key',
                                                ].includes(f.key) &&
                                                !f.key.startsWith('payment_method_') &&
                                                !f.key.startsWith('account_id_')
                                        )
                                        .map((field) => (
                                            <div key={field.key} style={{ marginBottom: 12 }}>
                                                {renderField(field.key)}

                                                {/* Show description below input (avoid duplicating the checkbox tooltip) */}
                                                {field.type !== 'checkbox' && field.description && (
                                                    <div
                                                        className="components-base-control__help"
                                                        dangerouslySetInnerHTML={{ __html: field.description }}
                                                    />
                                                )}
                                            </div>
                                        ))
                                )}

                                {showSandboxNotice && (
                                    <Notice status="warning" isDismissible={false}>
                                        Paysafe payments are in sandbox mode. You need to set up a live Paysafe account
                                        before you can accept real transactions.
                                    </Notice>
                                )}
                            </Card>
                        </div>
                    );
                })}

            {/* Sticky save bar */}
            <div
                style={{
                    position: 'fixed',
                    bottom: '20px',
                    right: '20px',
                    zIndex: 100,
                    background: '#fff',
                    padding: '12px 20px',
                    borderRadius: '8px',
                    boxShadow: '0 2px 6px rgba(0, 0, 0, 0.15)',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '16px',
                }}
            >
                <Button variant="primary" onClick={handleSubmit}>
                    Save Settings
                </Button>
                {status && <span>{status}</span>}
            </div>

            {/* ... Modal for per-method settings */}
            {activeMethodModal && (
                <Modal
                    title={`More options for ${
                        activeMethodModal.charAt(0).toUpperCase() + activeMethodModal.slice(1)
                    }`}
                    onRequestClose={() => setActiveMethodModal(null)}
                    style={{ width: 800 }}
                >
                    {Object.values(fields)
                        .flat()
                        .filter((f) => f.group?.toLowerCase() === activeMethodModal)
                        // only show env-matching fields: accept unsuffixed, or keys ending in current env
                        .filter((f) => !/_(sandbox|live)$/i.test(f.key) || f.key.endsWith(`_${activeTab}`))
                        .map((f) => {
                            const isWebhookField = f.key.startsWith('webhook_url_');
                            const descHtml = f.description || '';

                            const handleCopy = async () => {
                                const text = htmlToPlainText(descHtml);
                                try {
                                    await navigator.clipboard.writeText(text);
                                } catch {
                                    // Fallback for older browsers
                                    const ta = document.createElement('textarea');
                                    ta.value = text;
                                    document.body.appendChild(ta);
                                    ta.select();
                                    document.execCommand('copy');
                                    document.body.removeChild(ta);
                                }
                                setCopyStatus({ key: f.key, ok: true });
                                setTimeout(() => setCopyStatus(null), 2000);
                            };

                            return (
                                <div key={f.key} style={{ marginBottom: '16px' }}>
                                    {isWebhookField ? (
                                        <>
                                            {/* Title/intro for webhook setup (HTML from backend, supports <br>) */}
                                            {fieldByKey['webhook_title']?.description && (
                                                <div
                                                    style={{ marginBottom: '16px' }}
                                                    dangerouslySetInnerHTML={{ __html: fieldByKey['webhook_title'].description }}
                                                />
                                            )}

                                            <strong style={{ display: 'block', marginBottom: 4 }}>Webhook URL</strong>
                                            <div
                                                className="paysafe-webhook-url"
                                                dangerouslySetInnerHTML={{ __html: descHtml }}
                                            />
                                            <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                                                <Button isSecondary onClick={handleCopy}>Copy</Button>
                                                {copyStatus?.key === f.key && copyStatus.ok && (
                                                    <span style={{ fontSize: 12, color: '#1a7f37' }}>Copied to clipboard</span>
                                                )}
                                            </div>
                                        </>
                                    ) : (
                                        <>
                                            {renderField(f.key)}
                                            {f.description && (
                                                <div
                                                    className="components-base-control__help"
                                                    dangerouslySetInnerHTML={{ __html: f.description }}
                                                />
                                            )}
                                        </>
                                    )}
                                </div>
                            );
                        })}

                    <div style={{ marginTop: '20px' }}>
                        <Button isPrimary onClick={() => setActiveMethodModal(null)}>
                            Close
                        </Button>
                    </div>
                </Modal>
            )}

            {/* Credentials modal */}
            {showCredentialModal && (
                <Modal
                    title={`Configure ${activeTab === 'sandbox' ? 'Sandbox' : 'Live'} Credentials`}
                    onRequestClose={closeCredentialModal}
                    style={{ width: 800 }}
                >
                    <div className="configure-keys-fields">
                        <TextControl
                            label="Public API Key"
                            value={
                                modalKeyFields?.[
                                    activeTab === 'sandbox' ? 'test_public_api_key' : 'live_public_api_key'
                                    ] || ''
                            }
                            onChange={(val) =>
                                setModalKeyFields((prev) => ({
                                    ...prev,
                                    [activeTab === 'sandbox'
                                        ? 'test_public_api_key'
                                        : 'live_public_api_key']: val,
                                }))
                            }
                        />

                        {pubKeyField?.description && (
                            <div
                                className="components-base-control__help"
                                dangerouslySetInnerHTML={{ __html: pubKeyField.description }}
                            />
                        )}

                        <div style={{ position: 'relative', marginTop: '16px' }}>
                            <TextControl
                                label="Private API Key"
                                type={revealedKeys[activeTab + '_private_api_key'] ? 'text' : 'password'}
                                value={
                                    modalKeyFields?.[
                                        activeTab === 'sandbox' ? 'test_private_api_key' : 'live_private_api_key'
                                        ] || ''
                                }
                                onChange={(val) =>
                                    setModalKeyFields((prev) => ({
                                        ...prev,
                                        [activeTab === 'sandbox'
                                            ? 'test_private_api_key'
                                            : 'live_private_api_key']: val,
                                    }))
                                }
                                style={{ paddingRight: '40px' }}
                            />

                            <button
                                onClick={() =>
                                    setRevealedKeys((prev) => ({
                                        ...prev,
                                        [activeTab + '_private_api_key']: !prev[activeTab + '_private_api_key'],
                                    }))
                                }
                                style={{
                                    position: 'absolute',
                                    top: '50%',
                                    transform: 'translateY(-50%)',
                                    right: '10px',
                                    background: 'transparent',
                                    border: 'none',
                                    cursor: 'pointer',
                                    padding: 0,
                                    lineHeight: 1,
                                }}
                                aria-label="Toggle visibility"
                            >
                              <span
                                  className={`dashicons dashicons-${
                                      revealedKeys[activeTab + '_private_api_key'] ? 'hidden' : 'visibility'
                                  }`}
                                  style={{ fontSize: '18px', color: '#555' }}
                              />
                            </button>

                            {privKeyField?.description && (
                                <div
                                    className="components-base-control__help"
                                    dangerouslySetInnerHTML={{ __html: privKeyField.description }}
                                />
                            )}
                        </div>
                    </div>

                    {activeTab === 'live' && (
                        <div style={{ marginTop: '16px' }}>
                            <Notice status="warning" isDismissible={false}>
                                Under no circumstances should you ever share, give out, or use your secret API key for
                                anything other than secure server-to-server communication.
                            </Notice>
                        </div>
                    )}

                    {testConnectionNotice && (
                        <Notice
                            status={testConnectionNotice.status}
                            isDismissible={false}
                            style={{ marginTop: '16px' }}
                        >
                            {testConnectionNotice.message}
                        </Notice>
                    )}

                    <div
                        style={{
                            display: 'flex',
                            justifyContent: 'flex-start',
                            alignItems: 'center',
                            gap: '12px',
                            marginTop: '20px',
                        }}
                    >
                        <Button
                            variant="primary"
                            isBusy={testingConnection}
                            disabled={testingConnection}
                            onClick={handleTestConnection}
                            style={{
                                backgroundColor: '#3B5BF9',
                                borderColor: '#3B5BF9',
                                color: '#fff',
                            }}
                        >
                            Apply {activeTab === 'sandbox' ? 'Sandbox' : 'Live'} Credentials
                        </Button>

                        <Button isSecondary onClick={closeCredentialModal}>
                            Close
                        </Button>
                    </div>
                </Modal>
            )}
        </form>
    );
}
