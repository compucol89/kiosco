// File: src/components/productos/PricingQuickPanel.jsx
// Quick panel for dynamic pricing configuration in Products page
// Exists to allow editing pricing rules from the frontend
// Related files: api/pricing_control.php, api/pricing_save.php, api/pricing_config.php

import React, { useState, useEffect } from 'react';
import { TrendingUp, Clock, Calendar, AlertCircle, Check, X, Edit2, Save } from 'lucide-react';
import CONFIG from '../../config/config';

const PricingQuickPanel = ({ onClose }) => {
    const [status, setStatus] = useState(null);
    const [rules, setRules] = useState([]);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [editingRule, setEditingRule] = useState(null);

    const loadData = async () => {
        try {
            setLoading(true);
            
            // Cargar estado
            const statusRes = await fetch(`${CONFIG.API_URL}/api/pricing_control.php?action=status`);
            const statusData = await statusRes.json();
            if (statusData.success) setStatus(statusData.system);
            
            // Cargar reglas
            const rulesRes = await fetch(`${CONFIG.API_URL}/api/pricing_control.php?action=rules`);
            const rulesData = await rulesRes.json();
            if (rulesData.success) setRules(rulesData.rules);
            
        } catch (err) {
            console.error('Error cargando datos:', err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        loadData();
    }, []);

    const toggleSystem = async () => {
        if (!status) {
            alert('Esperando carga inicial...');
            return;
        }
        
        try {
            setSaving(true);
            const response = await fetch(`${CONFIG.API_URL}/api/pricing_save.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    action: 'toggle',
                    enabled: !status.enabled 
                }),
            });
            
            const data = await response.json();
            if (data.success) {
                setStatus({ ...status, enabled: data.enabled });
            } else {
                alert(data.message || 'Error al cambiar estado');
            }
        } catch (err) {
            console.error('Error:', err);
            alert('Error al cambiar estado: ' + err.message);
        } finally {
            setSaving(false);
        }
    };

    const saveRule = async (rule) => {
        try {
            setSaving(true);
            const response = await fetch(`${CONFIG.API_URL}/api/pricing_save.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'update_rule',
                    rule_id: rule.id,
                    updates: rule,
                }),
            });
            
            const data = await response.json();
            if (data.success) {
                await loadData();
                setEditingRule(null);
            }
        } catch (err) {
            console.error('Error:', err);
            alert('Error al guardar regla');
        } finally {
            setSaving(false);
        }
    };

    const getDayName = (day) => {
        const days = { mon: 'Lun', tue: 'Mar', wed: 'Mié', thu: 'Jue', fri: 'Vie', sat: 'Sáb', sun: 'Dom' };
        return days[day] || day;
    };

    if (loading) {
        return (
            <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                <div className="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[90vh] overflow-auto">
                    <div className="text-center">Cargando...</div>
                </div>
            </div>
        );
    }

    return (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div className="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[90vh] overflow-auto">
                {/* Header */}
                <div className="flex items-center justify-between mb-6">
                    <h2 className="text-2xl font-bold flex items-center gap-2">
                        <TrendingUp className="w-6 h-6 text-blue-600" />
                        Precios Dinámicos
                    </h2>
                    <button onClick={onClose} className="p-2 hover:bg-gray-100 rounded">
                        <X className="w-5 h-5" />
                    </button>
                </div>

                {/* Toggle Sistema */}
                <div className="mb-6 bg-gray-50 rounded-lg p-4 flex items-center justify-between">
                    <div>
                        <h3 className="font-semibold text-gray-900">Estado del Sistema</h3>
                        <p className="text-sm text-gray-600">
                            {!status ? 'Cargando...' : (status.enabled ? 'Los precios se ajustan automáticamente' : 'Sistema desactivado')}
                        </p>
                    </div>
                    <button
                        onClick={toggleSystem}
                        disabled={saving || !status}
                        className={`relative inline-flex h-8 w-14 items-center rounded-full transition-colors ${
                            status?.enabled ? 'bg-green-600' : 'bg-gray-300'
                        } ${saving || !status ? 'opacity-50' : ''}`}
                    >
                        <span className={`inline-block h-6 w-6 transform rounded-full bg-white transition-transform ${
                            status?.enabled ? 'translate-x-7' : 'translate-x-1'
                        }`} />
                    </button>
                </div>

                {/* Reglas */}
                <div>
                    <h3 className="font-semibold text-gray-900 mb-3">Reglas Configuradas</h3>
                    <div className="space-y-3">
                        {rules.map(rule => (
                            <div key={rule.id} className={`border rounded-lg p-4 ${
                                rule.enabled ? 'border-green-300 bg-green-50' : 'border-gray-300 bg-gray-50'
                            }`}>
                                {editingRule?.id === rule.id ? (
                                    // Modo edición
                                    <div className="space-y-3">
                                        <input
                                            type="text"
                                            value={editingRule.name}
                                            onChange={e => setEditingRule({...editingRule, name: e.target.value})}
                                            className="w-full px-3 py-2 border rounded"
                                            placeholder="Nombre de la regla"
                                        />
                                        
                                        <div className="grid grid-cols-2 gap-3">
                                            <div>
                                                <label className="text-sm text-gray-600">Desde</label>
                                                <input
                                                    type="time"
                                                    value={editingRule.from}
                                                    onChange={e => setEditingRule({...editingRule, from: e.target.value})}
                                                    className="w-full px-3 py-2 border rounded"
                                                />
                                            </div>
                                            <div>
                                                <label className="text-sm text-gray-600">Hasta</label>
                                                <input
                                                    type="time"
                                                    value={editingRule.to}
                                                    onChange={e => setEditingRule({...editingRule, to: e.target.value})}
                                                    className="w-full px-3 py-2 border rounded"
                                                />
                                            </div>
                                        </div>

                                        <div>
                                            <label className="text-sm text-gray-600">Ajuste (%)</label>
                                            <input
                                                type="number"
                                                step="0.1"
                                                value={editingRule.percent_inc}
                                                onChange={e => setEditingRule({...editingRule, percent_inc: parseFloat(e.target.value)})}
                                                className="w-full px-3 py-2 border rounded"
                                            />
                                        </div>

                                        <div className="flex gap-2">
                                            <button
                                                onClick={() => saveRule(editingRule)}
                                                disabled={saving}
                                                className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 flex items-center gap-2"
                                            >
                                                <Save className="w-4 h-4" />
                                                Guardar
                                            </button>
                                            <button
                                                onClick={() => setEditingRule(null)}
                                                className="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300"
                                            >
                                                Cancelar
                                            </button>
                                        </div>
                                    </div>
                                ) : (
                                    // Modo vista
                                    <>
                                        <div className="flex items-start justify-between mb-2">
                                            <div>
                                                <h4 className="font-semibold">{rule.name}</h4>
                                                <p className="text-sm text-gray-600">
                                                    {rule.type === 'category' ? '📦' : '🏷️'} {rule.target}
                                                </p>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <span className={`px-2 py-0.5 rounded text-xs font-medium ${
                                                    rule.enabled ? 'bg-green-200 text-green-800' : 'bg-gray-300 text-gray-700'
                                                }`}>
                                                    {rule.enabled ? 'Activa' : 'Inactiva'}
                                                </span>
                                                <button
                                                    onClick={() => setEditingRule(rule)}
                                                    className="p-1 hover:bg-white rounded"
                                                >
                                                    <Edit2 className="w-4 h-4" />
                                                </button>
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-3 gap-3 text-sm">
                                            <div>
                                                <span className="text-gray-600">📅 Días:</span>
                                                <p className="font-medium">{rule.days.map(getDayName).join(', ')}</p>
                                            </div>
                                            <div>
                                                <span className="text-gray-600">🕐 Horario:</span>
                                                <p className="font-medium">{rule.from} - {rule.to}</p>
                                            </div>
                                            <div>
                                                <span className="text-gray-600">💰 Ajuste:</span>
                                                <p className={`font-bold ${rule.percent_inc > 0 ? 'text-orange-600' : 'text-green-600'}`}>
                                                    {rule.percent_inc > 0 ? '+' : ''}{rule.percent_inc}%
                                                </p>
                                            </div>
                                        </div>
                                    </>
                                )}
                            </div>
                        ))}
                    </div>
                </div>

                {/* Info */}
                <div className="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm text-blue-800">
                    <AlertCircle className="w-4 h-4 inline mr-2" />
                    Los cambios se aplican inmediatamente en el POS
                </div>
            </div>
        </div>
    );
};

export default PricingQuickPanel;

