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
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

// key shapes handled:
//   payment_method_USD_CARD
//   payment_method_USD_CARD_sandbox
//   payment_method_USD_CARD_live
const parsePaymentKey = (key) => {
    const m = key.match(/^payment_method_([A-Z]{3})_([A-Z0-9]+)(?:_(sandbox|live))?$/i);
    if (!m) {
        const m2 = key.match(/^payment_method_([A-Z]{3})_([A-Z0-9_]+)_(sandbox|live)?$/i);
        if (!m2) return { currency: null, method: null, env: null };

        return { currency: m2[1], method: m2[2], env: m2[3] || null };
    }

    return { currency: m[1], method: m[2], env: m[3] || null };
};
function stableHash(obj) {
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

const GROUP_LABELS = {
    general: __('General', 'paysafe-checkout'),
    account: __('Account Settings', 'paysafe-checkout'),
    shopping_experience: __('Shopping Experience', 'paysafe-checkout'),
    payment_methods: __('Payment Methods', 'paysafe-checkout'),
    shopping_features: __('WooCommerce Subscriptions', 'paysafe-checkout'),
    advanced: __('Advanced Settings', 'paysafe-checkout'),
};


const SECTION_DESCRIPTIONS = {
    general: (
        <>
            <p>{ __('Enable or disable Paysafe payments.', 'paysafe-checkout') }</p>
            <p>{ __('If you enable test mode, the extension will connect to the Test environment. In this case, you must use test cards provided in the Paysafe documentation.', 'paysafe-checkout') }</p>
        </>
    ),
    account: (
        <>
            <p>
                {createInterpolateElement(
                    /* translators: <a> is a link to the Integrations team page */
                    __(
                        'Contact our <a>Integrations team</a> to get your Business Portal access and help setting up.',
                        'paysafe-checkout'
                    ),
                    {
                        a: (
                            <a
                                href="https://portals.paysafe.com/helpcenter/dashboard"
                                target="_blank"
                                rel="noreferrer"
                            />
                        ),
                    }
                )}
            </p>

            <p>{__('When you have access to the Business Portal, you can use it to manage all of your accounts and API keys.', 'paysafe-checkout')}</p>
            <p>{__('You need two API keys:', 'paysafe-checkout')}</p>
            <ul>
                <li>{__('Public key for browser-to-Paysafe communications.', 'paysafe-checkout')}</li>
                <li>{__('Private key for server-to-server calls.', 'paysafe-checkout')}</li>
            </ul>
            <p>
                <a href="https://portals.paysafe.com/login" target="_blank" rel="noreferrer">
                    {__('Visit our Merchant Portal for more details ↗', 'paysafe-checkout')}
                </a>
            </p>
        </>
    ),
    shopping_experience: (
        <p>{__('Basic settings related to the configuration of the Paysafe Checkout.', 'paysafe-checkout')}</p>),
    payment_methods: (
        <>
            <p>{__('Provide your customers with different payment methods that suit their needs.', 'paysafe-checkout')}</p>
            <p>{__('You can select only the payment methods that you have configured with Paysafe.', 'paysafe-checkout')}</p>
            <p>{ __('If you\'d like to enable additional methods, please contact customer support.', 'paysafe-checkout') }</p>
        </>
    ),
    shopping_features: (<p>{ __('Enable or disable WooCommerce subscriptions support.', 'paysafe-checkout') }</p>),
    advanced: (<p>{ __('Logging settings let you enable debug logs and choose whether to mask sensitive user data.', 'paysafe-checkout') }</p>),
};

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
    const envLabel = activeTab === 'sandbox' ? __('Sandbox', 'paysafe-checkout') : __('Live', 'paysafe-checkout');
    const [isSaving, setIsSaving] = useState(false);

    // Baseline snapshot of the last-saved form; compared to current `form`
    const baselineRef = useRef(null);

    // resolve the field objects for the active env
    const pubKeyId  = activeTab === 'sandbox' ? 'test_public_api_key'  : 'live_public_api_key';
    const privKeyId = activeTab === 'sandbox' ? 'test_private_api_key' : 'live_private_api_key';
    const pubKeyField  = Object.values(fields).flat().find(f => f.key === pubKeyId);
    const privKeyField = Object.values(fields).flat().find(f => f.key === privKeyId);

    const [copyStatus, setCopyStatus] = useState(null); // { key, ok } | null
    const isPluginEnabled = form.enabled === 'yes';

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
                setError( __('Failed to load settings.', 'paysafe-checkout'));
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
        setStatus(__('Saving...', 'paysafe-checkout'));
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

                setStatus(__('Settings saved!', 'paysafe-checkout'));
                // Optional: clear the status after a short delay
                setTimeout(() => setStatus(''), 2500);
            })
            .catch(() => {
                setStatus(__('Save failed.', 'paysafe-checkout'));
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
                <div className="paysafe-checkbox-row" tabIndex={0} role="group" aria-label={field.description}>
                    <ToggleControl
                        checked={value === 'yes'}
                        onChange={(v) => handleChange(id, v ? 'yes' : 'no')}
                        aria-label={field.title}
                    />
                    <div className="paysafe-checkbox-row__text">
                        <div className="paysafe-checkbox-row__title">{field.title}</div>
                        <div className="paysafe-checkbox-row__label">{field.label}</div>
                    </div>
                </div>
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
                            { __('Show less', 'paysafe-checkout') }
                        </Button>
                    </>
                ) : (
                    <div className="paysafe-api-key-wrapper">
                        {shortValue}
                        <span className="paysafe-ellipsis">...</span>{' '}
                        <Button
                            variant="link"
                            onClick={() => toggleKeyVisibility(keyName)}
                            className="paysafe-show-toggle paysafe-show-more"
                        >
                            { __('Show more', 'paysafe-checkout') }
                        </Button>
                    </div>
                )}
            </div>
        );
    };


    const renderCredentialSummary = () => (
        <TabPanel
            key={`account-${activeTab}`}
            className="components-tab-panel paysafe-credentials-summary"
            activeClass="is-active"
            tabs={[
                { name: 'sandbox', title: __('Sandbox', 'paysafe-checkout') },
                { name: 'live',    title: __('Live', 'paysafe-checkout') },
            ]}
            onSelect={(tabName) => setActiveTab(tabName)}
            initialTabName={activeTab}
        >
            {(tab) => {
                const isSandbox = tab.name === 'sandbox';
                const pubKey = isSandbox ? form.test_public_api_key : form.live_public_api_key;
                const privKey = isSandbox ? form.test_private_api_key : form.live_private_api_key;

                return (
                    <div className="paysafe-cred-summary">
                        {(pubKey && privKey) ? (
                                <>
                                    <div className="paysafe-cred-summary__block">
                                        <strong className="paysafe-cred-summary__label">{ __('Public API Key', 'paysafe-checkout') }</strong>
                                        {renderApiKey(pubKey, 'public')}
                                    </div>

                                    <div className="paysafe-cred-summary__block">
                                        <strong className="paysafe-cred-summary__label">{ __('Private API Key', 'paysafe-checkout') }</strong>
                                        {renderApiKey(privKey, 'private')}
                                    </div>
                                </>
                            ) :
                            <Notice status="warning" isDismissible={false}>
                                { __('Please configure your API keys!', 'paysafe-checkout') }
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
                        message: __('You successfully applied your API credentials. You can now close this dialog.', 'paysafe-checkout'),
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
                        message: data.message || __('An unknown error occurred.', 'paysafe-checkout'),
                    });
                }
            })
            .catch(() => {
                setTestConnectionNotice({
                    status: 'error',
                    message: __('An error occurred while testing the connection.', 'paysafe-checkout'),
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
            <div className={`paysafe-spinner-wrapper ${fadeOut ? 'fade-out' : ''}`}>
                <Spinner />
                <p className="paysafe-spinner-text">{ __('Loading settings…', 'paysafe-checkout') }</p>
            </div>
        );
    }

    return (
        <form onSubmit={handleSubmit} className="paysafe-form">
            <h1>{__('Paysafe Checkout Settings', 'paysafe-checkout')}</h1>

            {/* Top warning if the gateway is disabled */}
            { form?.enabled !== 'yes' && (
                <Notice
                    status="warning"
                    isDismissible={false}
                    className="paysafe-disabled-notice"
                >
                    { __(
                        'The Paysafe extension is currently disabled. Customers will not see Paysafe payment methods' +
                        ' at checkout until you enable it.',
                        'paysafe-checkout'
                    ) }
                </Notice>
            )}

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
                        <div key={groupKey} className="paysafe-section">
                            <div className="paysafe-section__left">
                                <h2>{GROUP_LABELS[groupKey]}</h2>
                                {SECTION_DESCRIPTIONS[groupKey]}
                            </div>

                            <Card className="paysafe-section__card">
                                {showCredentialTabs && renderCredentialSummary()}

                                {showCredentialTabs && (
                                    <Button
                                        variant="primary"
                                        onClick={openCredentialModal}
                                        className="paysafe-configure-keys-btn"
                                    >
                                        { __('Configure keys', 'paysafe-checkout') }
                                    </Button>
                                )}

                                {groupKey === 'payment_methods' ? (
                                    <div className="paysafe-pm">
                                        {/* Top env tabs for payment methods */}
                                        <TabPanel
                                            key={`payment-${activeTab}`}
                                            className="paysafe-payment-tabs"
                                            activeClass="is-active"
                                            tabs={[
                                                { name: 'sandbox', title: __('Sandbox', 'paysafe-checkout') },
                                                { name: 'live',    title: __('Live', 'paysafe-checkout') },
                                            ]}
                                            onSelect={(tabName) => setActiveTab(tabName)}
                                            initialTabName={activeTab}
                                        >
                                            {() => null}
                                        </TabPanel>

                                        {activeTab === 'sandbox' && (
                                            <div className="paysafe-warning-block">
                                                <Notice status="warning" isDismissible={false}>
                                                    { __('You are configuring test payment methods.', 'paysafe-checkout') }
                                                </Notice>
                                            </div>
                                        )}

                                        {groupFields
                                            // show only rows for the current env; accept unsuffixed keys as current env (b/c)
                                            .filter((f) => {
                                                if (!f.key.startsWith('payment_method_')) return false;
                                                const {env} = parsePaymentKey(f.key);
                                                return (env ?? activeTab) === activeTab;
                                            })
                                            .map((methodField) => {
                                                const {currency, method, env} = parsePaymentKey(methodField.key);
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
                                                    <div key={methodField.key} className="paysafe-pm-row">
                                                        <div
                                                            className="paysafe-pm-row__col paysafe-pm-row__col--toggle">

                                                            {isDisabled ? (
                                                                <Tooltip
                                                                    text={
                                                                        (activeTab === 'sandbox' && !form.test_public_api_key) || (activeTab === 'live' && !form.live_public_api_key)
                                                                            ? __('Please configure your API keys', 'paysafe-checkout')
                                                                            : __('Please enable it in your merchant portal', 'paysafe-checkout')
                                                                    }
                                                                >
                                                                <span
                                                                    className="dashicons dashicons-info-outline paysafe-info-icon"
                                                                    tabIndex={0}
                                                                    role="img"
                                                                />
                                                                </Tooltip>
                                                            ) : (
                                                                <ToggleControl
                                                                    label=""
                                                                    checked={enabled}
                                                                    onChange={(v) => handleChange(methodField.key, v ? 'yes' : 'no')}
                                                                    /* translators: %s: Integrations team anchor tag */
                                                                    aria-label={ sprintf(__('Enable %s', 'paysafe-checkout'), methodField.title) }
                                                                />
                                                            )}
                                                        </div>

                                                        {/* Column 2: icon/title/desc */}
                                                        <div className="paysafe-pm-row__col paysafe-pm-row__col--main">
                                                            <div className="paysafe-pm-row__icon">
                                                                <img
                                                                    src={icon}
                                                                    alt={methodField.title}
                                                                    className="paysafe-pm-row__icon-img"
                                                                    onError={(e) => {
                                                                        e.currentTarget.style.display = 'none';
                                                                    }}
                                                                />
                                                            </div>
                                                            <div
                                                                className="paysafe-pm-row__desc">{methodField.description}
                                                            </div>
                                                        </div>

                                                        {/* Column 3: account id select */}
                                                        {accountField && (
                                                            <div className="paysafe-pm-row__col paysafe-account-select">
                                                                {renderField(accountField.key)}
                                                            </div>
                                                        )}

                                                        {/* Column 4: ... */}
                                                        {!isDisabled && (
                                                            <div
                                                                className="paysafe-pm-row__col paysafe-pm-row__col--more">
                                                                {showMoreButton && (
                                                                    <Button
                                                                        icon="ellipsis"
                                                                        aria-label={ __('More options', 'paysafe-checkout') }
                                                                        onClick={() => setActiveMethodModal(methodLower)}
                                                                        className="paysafe-more-btn"
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
                                        <div dangerouslySetInnerHTML={{__html: subscriptionNotice}}/>
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
                                            <div key={field.key} className="paysafe-field-wrap">
                                                {renderField(field.key)}

                                                {/* Show description below input (avoid duplicating the checkbox tooltip) */}
                                                {field.type !== 'checkbox' && field.description && (
                                                    <div
                                                        className="components-base-control__help"
                                                        dangerouslySetInnerHTML={{__html: field.description}}
                                                    />
                                                )}
                                            </div>
                                        ))
                                )}

                                {showSandboxNotice && (
                                    <Notice status="warning" isDismissible={false}>
                                        { __('Paysafe payments are in sandbox mode. You need to set up a live Paysafe account before you can accept real transactions.', 'paysafe-checkout') }
                                    </Notice>
                                )}
                            </Card>
                        </div>
                    );
                })}

            {/* Sticky save bar */}
            <div className="paysafe-sticky-bar">
                <Button variant="primary" onClick={handleSubmit}>
                    { __('Save Settings', 'paysafe-checkout') }
                </Button>
                {status && <span>{status}</span>}
            </div>

            {/* ... Modal for per-method settings */}
            {activeMethodModal && (
                <Modal
                     title={ sprintf(
                         /* translators: %s: Integrations team anchor tag */
                       __('More options for %s', 'paysafe-checkout'),
                       activeMethodModal.charAt(0).toUpperCase() + activeMethodModal.slice(1)
                     ) }
                    onRequestClose={() => setActiveMethodModal(null)}
                    className="paysafe-modal paysafe-modal--method"
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
                                setCopyStatus({key: f.key, ok: true});
                                setTimeout(() => setCopyStatus(null), 2000);
                            };

                            return (
                                <div key={f.key} className="paysafe-modal__field">
                                    {isWebhookField ? (
                                        <>
                                            {/* Title/intro for webhook setup (HTML from backend, supports <br>) */}
                                            {fieldByKey['webhook_title']?.description && (
                                                <div
                                                    className="paysafe-webhook-title"
                                                    dangerouslySetInnerHTML={{__html: fieldByKey['webhook_title'].description}}
                                                />
                                            )}

                                            <strong className="paysafe-webhook-label">{ __('Webhook URL', 'paysafe-checkout') }</strong>
                                            <div
                                                className="paysafe-webhook-url"
                                                dangerouslySetInnerHTML={{__html: descHtml}}
                                            />
                                            <div className="paysafe-webhook-actions">
                                                <Button isSecondary onClick={handleCopy}>{ __('Copy', 'paysafe-checkout') }</Button>
                                                {copyStatus?.key === f.key && copyStatus.ok && (
                                                    <span className="paysafe-copied-note">{ __('Copied to clipboard', 'paysafe-checkout') }</span>
                                                )}
                                            </div>
                                        </>
                                    ) : (
                                        <>
                                            {renderField(f.key)}
                                            {f.description && (
                                                <div
                                                    className="components-base-control__help"
                                                    dangerouslySetInnerHTML={{__html: f.description}}
                                                />
                                            )}
                                        </>
                                    )}
                                </div>
                            );
                        })}

                    <div className="paysafe-modal__footer">
                        <Button isPrimary onClick={() => setActiveMethodModal(null)}>
                            { __('Close', 'paysafe-checkout') }
                        </Button>
                    </div>
                </Modal>
            )}

            {/* Credentials modal */}
            {showCredentialModal && (
                <Modal
                    title={ sprintf(
                        /* translators: %s: Integrations team anchor tag */
                        __('Configure %s Credentials', 'paysafe-checkout'),
                        envLabel
                    ) }
                    onRequestClose={closeCredentialModal}
                    className="paysafe-modal paysafe-modal--credentials"
                >
                    <div className="configure-keys-fields">
                        <TextControl
                            label={ __('Public API Key', 'paysafe-checkout') }
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
                                dangerouslySetInnerHTML={{__html: pubKeyField.description}}
                            />
                        )}


                        <div className="paysafe-private-key">
                            <TextControl
                                label={ __('Private API Key', 'paysafe-checkout') }
                                type={revealedKeys[activeTab + '_private_api_key'] ? 'text' : 'password'}
                                value={modalKeyFields?.[activeTab === 'sandbox' ? 'test_private_api_key' : 'live_private_api_key'] || ''}
                                onChange={(val) => setModalKeyFields(prev => ({
                                    ...prev,
                                    [activeTab === 'sandbox' ? 'test_private_api_key' : 'live_private_api_key']: val,
                                }))}
                            />
                            <button
                                className="paysafe-eye-btn"
                                onClick={() => setRevealedKeys(prev => ({
                                    ...prev,
                                    [activeTab + '_private_api_key']: !prev[activeTab + '_private_api_key'],
                                }))}
                                aria-label={ __('Toggle visibility', 'paysafe-checkout') }
                            >
                                <span
                                    className={`dashicons dashicons-${revealedKeys[activeTab + '_private_api_key'] ? 'hidden' : 'visibility'} paysafe-eye-icon`}
                                />
                            </button>

                            {/* help text can remain after; it's fine */}
                            {privKeyField?.description && (
                                <div
                                    className="components-base-control__help"
                                    dangerouslySetInnerHTML={{__html: privKeyField.description}}
                                />
                            )}
                        </div>

                    </div>

                    {activeTab === 'live' && (
                        <div className="paysafe-live-warning">
                            <Notice status="warning" isDismissible={false}>
                                { __('Under no circumstances should you ever share, give out, or use your secret API key for anything other than secure server-to-server communication.', 'paysafe-checkout') }
                            </Notice>
                        </div>
                    )}

                    {testConnectionNotice && (
                        <Notice
                            status={testConnectionNotice.status}
                            isDismissible={false}
                            className="paysafe-testconn-notice"
                        >
                            {testConnectionNotice.message}
                        </Notice>
                    )}

                    <div className="paysafe-modal__actions">
                        <Button
                            variant="primary"
                            isBusy={testingConnection}
                            disabled={testingConnection}
                            onClick={handleTestConnection}
                            className="paysafe-apply-btn"
                        >
                            {
                                /* translators: %s: Integrations team anchor tag */
                                sprintf( __('Apply %s Credentials', 'paysafe-checkout'), envLabel )
                            }
                        </Button>

                        <Button isSecondary onClick={closeCredentialModal}>
                            { __('Close', 'paysafe-checkout') }
                        </Button>
                    </div>
                </Modal>
            )}
        </form>
    );
}
