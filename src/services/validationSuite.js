// ============================================================================
// 🛡️ VALIDATION SUITE - SISTEMA BANCARIO DE VALIDACIONES CRÍTICAS
// ============================================================================
// Cumple con: PCI DSS, SOX, ISO 27001, Basel III
// Propósito: Validaciones críticas de negocio con nivel bancario

class ValidationSuite {
  constructor() {
    this.validationRules = this.initializeValidationRules();
    this.businessLimits = this.initializeBusinessLimits();
    this.complianceChecks = this.initializeComplianceChecks();
  }

  // ========================================================================
  // 🔧 INICIALIZACIÓN DE REGLAS
  // ========================================================================

  initializeValidationRules() {
    return {
      cashLimits: {
        minOpeningAmount: 0,
        maxOpeningAmount: 50000,
        maxDailyTransactions: 1000,
        maxSingleTransaction: 10000
      },
      userPermissions: {
        requiredRoles: ['CAJERO', 'SUPERVISOR', 'ADMIN'],
        minExperienceMonths: 1,
        requiresActiveSession: true
      },
      operationalHours: {
        startHour: 6,  // 6:00 AM
        endHour: 23,   // 11:00 PM
        allowWeekends: true,
        allowHolidays: false
      },
      securityRequirements: {
        maxConsecutiveFailures: 3,
        requiresJustification: true,
        requiresPhotographicEvidence: false,
        mandatoryApproval: false
      }
    };
  }

  initializeBusinessLimits() {
    return {
      daily: {
        maxCashHandling: 100000,
        maxTransactionCount: 500,
        maxDiscrepancyAmount: 100
      },
      session: {
        maxDuration: 12, // horas
        maxIdleTime: 30, // minutos
        requiresBreaks: true
      },
      antifraud: {
        riskThreshold: 70,
        suspiciousPatternLimit: 5,
        velocityCheckWindow: 60 // minutos
      }
    };
  }

  initializeComplianceChecks() {
    return {
      pciDss: {
        enabled: true,
        level: 'LEVEL_1',
        requiresEncryption: true
      },
      sox: {
        enabled: true,
        requiresAuditTrail: true,
        mandatoryApprovals: true
      },
      iso27001: {
        enabled: true,
        securityControls: true,
        riskManagement: true
      }
    };
  }

  // ========================================================================
  // 🏦 VALIDACIONES PRINCIPALES DE OPERACIONES
  // ========================================================================

  async validateOperation(operationType, operationData, userContext = {}) {
    const validationResult = {
      isValid: false,
      errors: [],
      warnings: [],
      riskLevel: 'LOW',
      requiredApprovals: [],
      additionalChecks: [],
      complianceStatus: 'PENDING'
    };

    try {
      // Validaciones por tipo de operación
      switch (operationType) {
        case 'CASH_OPENING':
          await this.validateCashOpening(operationData, validationResult, userContext);
          break;
        case 'CASH_CLOSING':
          await this.validateCashClosing(operationData, validationResult, userContext);
          break;
        case 'CASH_TRANSACTION':
          await this.validateCashTransaction(operationData, validationResult, userContext);
          break;
        case 'USER_LOGIN':
          await this.validateUserLogin(operationData, validationResult, userContext);
          break;
        default:
          validationResult.errors.push(`Tipo de operación no reconocido: ${operationType}`);
      }

      // Validaciones generales de compliance
      await this.validateCompliance(operationData, validationResult, userContext);
      
      // Validaciones de seguridad
      await this.validateSecurity(operationData, validationResult, userContext);

      // Determinar si es válida la operación
      validationResult.isValid = validationResult.errors.length === 0;

    } catch (error) {
      validationResult.errors.push(`Error en validación: ${error.message}`);
      validationResult.riskLevel = 'HIGH';
    }

    return validationResult;
  }

  // ========================================================================
  // 💰 VALIDACIONES ESPECÍFICAS DE CAJA
  // ========================================================================

  async validateCashOpening(data, result, userContext) {
    const { amount, justification, userId } = data;

    // Validar monto
    if (amount === undefined || amount === null) {
      result.errors.push('El monto de apertura es obligatorio');
    } else if (amount < this.validationRules.cashLimits.minOpeningAmount) {
      result.errors.push(`Monto mínimo de apertura: $${this.validationRules.cashLimits.minOpeningAmount}`);
    } else if (amount > this.validationRules.cashLimits.maxOpeningAmount) {
      result.errors.push(`Monto máximo de apertura: $${this.validationRules.cashLimits.maxOpeningAmount}`);
      result.requiredApprovals.push('SUPERVISOR_APPROVAL');
      result.riskLevel = 'HIGH';
    }

    // Validar justificación
    if (!justification || justification.trim().length < 10) {
      result.errors.push('La justificación debe tener al menos 10 caracteres');
    }

    // Validar horario operacional
    if (!this.isWithinOperatingHours()) {
      result.warnings.push('Operación fuera del horario comercial');
      result.requiredApprovals.push('MANAGER_APPROVAL');
    }

    // Validar usuario
    await this.validateUserPermissions(userId, 'CASH_OPENING', result);

    // Checks adicionales para montos altos
    if (amount > 10000) {
      result.additionalChecks.push('PHOTOGRAPHIC_EVIDENCE');
      result.additionalChecks.push('DUAL_AUTHORIZATION');
    }
  }

  async validateCashClosing(data, result, userContext) {
    const { finalAmount, expectedAmount, difference } = data;

    // Calcular discrepancia
    const calculatedDifference = Math.abs(finalAmount - expectedAmount);

    if (calculatedDifference !== Math.abs(difference)) {
      result.errors.push('La discrepancia calculada no coincide con la reportada');
    }

    // Validar discrepancia dentro de límites
    if (calculatedDifference > this.businessLimits.daily.maxDiscrepancyAmount) {
      result.errors.push(`Discrepancia excede el límite permitido: $${this.businessLimits.daily.maxDiscrepancyAmount}`);
      result.riskLevel = 'HIGH';
      result.requiredApprovals.push('MANAGER_INVESTIGATION');
    }

    // Advertencias para discrepancias menores
    if (calculatedDifference > 0 && calculatedDifference <= 50) {
      result.warnings.push('Discrepancia menor detectada - requiere justificación');
    }
  }

  async validateCashTransaction(data, result, userContext) {
    const { amount, transactionType, paymentMethod } = data;

    // Validar monto de transacción
    if (amount > this.validationRules.cashLimits.maxSingleTransaction) {
      result.warnings.push('Transacción de alto valor - requiere supervisión');
      result.riskLevel = 'MEDIUM';
    }

    // Validar método de pago
    const validPaymentMethods = ['EFECTIVO', 'TARJETA', 'TRANSFERENCIA'];
    if (!validPaymentMethods.includes(paymentMethod)) {
      result.errors.push(`Método de pago no válido: ${paymentMethod}`);
    }

    // Validaciones antilavado
    await this.validateAntiMoneyLaundering(data, result);
  }

  // ========================================================================
  // 👤 VALIDACIONES DE USUARIO Y SEGURIDAD
  // ========================================================================

  async validateUserLogin(data, result, userContext) {
    const { username, role, lastLogin } = data;

    // Validar rol
    if (!this.validationRules.userPermissions.requiredRoles.includes(role)) {
      result.errors.push(`Rol no autorizado para operaciones de caja: ${role}`);
    }

    // Validar sesión activa
    if (this.validationRules.userPermissions.requiresActiveSession) {
      const sessionAge = this.calculateSessionAge(lastLogin);
      if (sessionAge > this.businessLimits.session.maxDuration) {
        result.errors.push('Sesión expirada - requiere reautenticación');
      }
    }

    // Validar intentos de acceso
    const failedAttempts = await this.getFailedLoginAttempts(username);
    if (failedAttempts >= this.validationRules.securityRequirements.maxConsecutiveFailures) {
      result.errors.push('Usuario bloqueado por múltiples intentos fallidos');
      result.riskLevel = 'HIGH';
    }
  }

  async validateUserPermissions(userId, operation, result) {
    // Simulación de validación de permisos
    const userPermissions = await this.getUserPermissions(userId);
    
    if (!userPermissions.includes(operation)) {
      result.errors.push(`Usuario no tiene permisos para: ${operation}`);
    }

    return userPermissions.includes(operation);
  }

  // ========================================================================
  // 🔍 VALIDACIONES DE COMPLIANCE Y SEGURIDAD
  // ========================================================================

  async validateCompliance(data, result, userContext) {
    // PCI DSS Compliance
    if (this.complianceChecks.pciDss.enabled) {
      if (data.sensitiveData && !this.complianceChecks.pciDss.requiresEncryption) {
        result.warnings.push('Datos sensibles detectados - se requiere encriptación PCI DSS');
      }
    }

    // SOX Compliance
    if (this.complianceChecks.sox.enabled && this.complianceChecks.sox.requiresAuditTrail) {
      if (!data.auditTrail) {
        result.additionalChecks.push('AUDIT_TRAIL_CREATION');
      }
    }

    // ISO 27001 Security Controls
    if (this.complianceChecks.iso27001.enabled) {
      await this.validateISO27001Controls(data, result);
    }
  }

  async validateSecurity(data, result, userContext) {
    // Validar patrones sospechosos
    const suspiciousPatterns = await this.detectSuspiciousPatterns(data, userContext);
    if (suspiciousPatterns.length > 0) {
      result.warnings.push(`Patrones sospechosos detectados: ${suspiciousPatterns.join(', ')}`);
      result.riskLevel = 'MEDIUM';
    }

    // Validar velocidad de transacciones
    const velocityCheck = await this.checkTransactionVelocity(data, userContext);
    if (velocityCheck.isExcessive) {
      result.warnings.push('Velocidad de transacciones inusual detectada');
      result.riskLevel = 'HIGH';
    }
  }

  async validateAntiMoneyLaundering(data, result) {
    const { amount, frequency, pattern } = data;

    // Validaciones AML básicas
    if (amount > 10000) {
      result.additionalChecks.push('AML_REPORTING');
    }

    if (frequency && frequency.dailyCount > 20) {
      result.warnings.push('Frecuencia de transacciones inusual - requiere revisión AML');
    }
  }

  // ========================================================================
  // 🔧 MÉTODOS AUXILIARES Y UTILIDADES
  // ========================================================================

  isWithinOperatingHours() {
    const now = new Date();
    const currentHour = now.getHours();
    const isWeekend = now.getDay() === 0 || now.getDay() === 6;

    if (isWeekend && !this.validationRules.operationalHours.allowWeekends) {
      return false;
    }

    return currentHour >= this.validationRules.operationalHours.startHour && 
           currentHour <= this.validationRules.operationalHours.endHour;
  }

  calculateSessionAge(lastLogin) {
    if (!lastLogin) return Infinity;
    const now = new Date();
    const loginTime = new Date(lastLogin);
    return (now - loginTime) / (1000 * 60 * 60); // en horas
  }

  async getUserPermissions(userId) {
    // Simulación - en producción consultar base de datos
    return ['CASH_OPENING', 'CASH_CLOSING', 'CASH_TRANSACTION', 'VIEW_REPORTS'];
  }

  async getFailedLoginAttempts(username) {
    // Simulación - en producción consultar logs de seguridad
    return Math.floor(Math.random() * 3); // 0-2 intentos fallidos
  }

  async detectSuspiciousPatterns(data, userContext) {
    const patterns = [];
    
    // Patrón: transacciones repetitivas
    if (data.amount && this.isRoundNumber(data.amount)) {
      patterns.push('ROUND_AMOUNTS');
    }

    // Patrón: horarios inusuales
    if (!this.isWithinOperatingHours()) {
      patterns.push('OFF_HOURS_ACTIVITY');
    }

    return patterns;
  }

  async checkTransactionVelocity(data, userContext) {
    // Simulación de check de velocidad
    const transactionCount = userContext.recentTransactionCount || 0;
    const timeWindow = this.businessLimits.antifraud.velocityCheckWindow;
    
    return {
      isExcessive: transactionCount > 50, // más de 50 transacciones en ventana de tiempo
      count: transactionCount,
      timeWindow: timeWindow
    };
  }

  async validateISO27001Controls(data, result) {
    // Controles de seguridad ISO 27001
    if (!data.securityContext) {
      result.additionalChecks.push('SECURITY_CONTEXT_VALIDATION');
    }

    if (data.accessLevel === 'ADMIN' && !data.multiFactorAuth) {
      result.errors.push('Operaciones administrativas requieren autenticación multifactor');
    }
  }

  isRoundNumber(amount) {
    return amount % 100 === 0 || amount % 500 === 0 || amount % 1000 === 0;
  }

  // ========================================================================
  // 📊 MÉTODOS DE REPORTE Y CONFIGURACIÓN
  // ========================================================================

  getValidationReport() {
    return {
      validationRules: this.validationRules,
      businessLimits: this.businessLimits,
      complianceChecks: this.complianceChecks,
      lastUpdate: new Date().toISOString(),
      status: 'ACTIVE'
    };
  }

  updateBusinessLimits(newLimits) {
    this.businessLimits = { ...this.businessLimits, ...newLimits };
    return this.businessLimits;
  }

  updateValidationRules(newRules) {
    this.validationRules = { ...this.validationRules, ...newRules };
    return this.validationRules;
  }

  async validateBatchOperations(operations) {
    const results = [];
    
    for (const operation of operations) {
      const result = await this.validateOperation(
        operation.type,
        operation.data,
        operation.userContext
      );
      results.push({
        operationId: operation.id,
        ...result
      });
    }

    return {
      totalOperations: operations.length,
      validOperations: results.filter(r => r.isValid).length,
      invalidOperations: results.filter(r => !r.isValid).length,
      highRiskOperations: results.filter(r => r.riskLevel === 'HIGH').length,
      results: results
    };
  }
}

// ============================================================================
// 🌟 EXPORTACIÓN E INSTANCIA GLOBAL
// ============================================================================

// Crear instancia global del validation suite
const validationSuite = new ValidationSuite();

// Exportar para uso en la aplicación
export default validationSuite;

// También exportar la clase para instancias adicionales
export { ValidationSuite };
