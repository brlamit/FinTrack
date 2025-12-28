import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import '../../../services/api_service.dart';

class GroupsScreen extends StatefulWidget {
  const GroupsScreen({super.key});

  @override
  State<GroupsScreen> createState() => _GroupsScreenState();
}

class _GroupsScreenState extends State<GroupsScreen> {
  bool _loading = true;
  String? _error;
  List<dynamic> _groups = [];

  @override
  void initState() {
    super.initState();
    _loadGroups();
  }

  Future<void> _loadGroups() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final groups = await ApiService.fetchGroupsApi();
      setState(() {
        _groups = groups;
      });
    } catch (e) {
      setState(() {
        _error = e.toString();
      });
    } finally {
      setState(() {
        _loading = false;
      });
    }
  }

  Future<void> _openCreateGroup() async {
    final created = await Navigator.push<bool>(
      context,
      MaterialPageRoute(builder: (_) => const EditGroupScreen()),
    );
    if (created == true) {
      _loadGroups();
    }
  }

  Future<void> _openGroupDetail(Map<String, dynamic> group) async {
    await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => GroupDetailScreen(groupId: group['id'] as int),
      ),
    );
  }

  Future<void> _deleteGroup(int id) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete group'),
        content: const Text('Are you sure you want to delete this group?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Delete'),
          ),
        ],
      ),
    );

    if (confirm != true) return;

    try {
      await ApiService.deleteGroupApi(id);
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Group deleted')));
      _loadGroups();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Failed to delete group: $e')));
    }
  }

  bool _isCurrentUserAdmin(Map<String, dynamic> group) {
    final currentUser = ApiService.currentUser;
    if (currentUser == null) return false;

    final currentUserId = currentUser['id'];
    final members = group['members'] as List<dynamic>? ?? [];

    // Check if current user is the owner
    if (group['owner_id'] == currentUserId) return true;

    // Check if current user has admin role in members
    for (final member in members) {
      if (member['user_id'] == currentUserId && member['role'] == 'admin') {
        return true;
      }
    }

    return false;
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: const Text('My Groups'),
        backgroundColor: theme.colorScheme.primary,
        foregroundColor: theme.colorScheme.onPrimary,
        actions: [
          IconButton(
            onPressed: _loadGroups,
            icon: const Icon(Icons.refresh),
            tooltip: 'Refresh',
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: _openCreateGroup,
        backgroundColor: theme.colorScheme.primary,
        foregroundColor: theme.colorScheme.onPrimary,
        elevation: 4,
        child: const Icon(Icons.group_add),
      ),
      body: Container(
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: theme.brightness == Brightness.light
                ? [
                    theme.colorScheme.surface,
                    theme.colorScheme.surfaceContainerLowest,
                  ]
                : [
                    theme.colorScheme.surface,
                    theme.colorScheme.surfaceContainerHighest.withValues(
                      alpha: 77,
                    ),
                  ],
          ),
        ),
        child: SafeArea(
          child: _loading
              ? const Center(child: CircularProgressIndicator())
              : _error != null
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(20.0),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Container(
                          padding: const EdgeInsets.all(20),
                          decoration: BoxDecoration(
                            color: theme.colorScheme.error.withValues(
                              alpha: 26,
                            ),
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(
                              color: theme.colorScheme.error.withValues(
                                alpha: 51,
                              ),
                            ),
                          ),
                          child: Icon(
                            Icons.error_outline,
                            color: theme.colorScheme.error,
                            size: 48,
                          ),
                        ),
                        const SizedBox(height: 16),
                        Text(
                          'Failed to load groups',
                          style: theme.textTheme.titleMedium?.copyWith(
                            color: theme.colorScheme.error,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          _error!,
                          textAlign: TextAlign.center,
                          style: theme.textTheme.bodyMedium?.copyWith(
                            color: theme.colorScheme.onSurface.withValues(
                              alpha: 179,
                            ),
                          ),
                        ),
                        const SizedBox(height: 20),
                        ElevatedButton.icon(
                          onPressed: _loadGroups,
                          icon: const Icon(Icons.refresh),
                          label: const Text('Try Again'),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: theme.colorScheme.primary,
                            foregroundColor: theme.colorScheme.onPrimary,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                )
              : _groups.isEmpty
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(20.0),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Container(
                          padding: const EdgeInsets.all(24),
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                              colors: [
                                theme.colorScheme.primary.withValues(alpha: 26),
                                theme.colorScheme.primary.withValues(alpha: 13),
                              ],
                            ),
                            borderRadius: BorderRadius.circular(20),
                            border: Border.all(
                              color: theme.colorScheme.primary.withValues(
                                alpha: 51,
                              ),
                            ),
                          ),
                          child: Icon(
                            Icons.groups_outlined,
                            color: theme.colorScheme.primary,
                            size: 64,
                          ),
                        ),
                        const SizedBox(height: 24),
                        Text(
                          'No Groups Yet',
                          style: theme.textTheme.titleLarge?.copyWith(
                            fontWeight: FontWeight.bold,
                            color: theme.colorScheme.onSurface,
                          ),
                        ),
                        const SizedBox(height: 12),
                        Text(
                          'Create a group to start splitting expenses with friends or family.',
                          textAlign: TextAlign.center,
                          style: theme.textTheme.bodyMedium?.copyWith(
                            color: theme.colorScheme.onSurface.withValues(
                              alpha: 179,
                            ),
                          ),
                        ),
                        const SizedBox(height: 32),
                        ElevatedButton.icon(
                          onPressed: _openCreateGroup,
                          icon: const Icon(Icons.group_add),
                          label: const Text('Create Your First Group'),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: theme.colorScheme.primary,
                            foregroundColor: theme.colorScheme.onPrimary,
                            padding: const EdgeInsets.symmetric(
                              horizontal: 24,
                              vertical: 12,
                            ),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(16),
                            ),
                            elevation: 4,
                          ),
                        ),
                      ],
                    ),
                  ),
                )
              : ListView.separated(
                  padding: const EdgeInsets.all(20.0),
                  itemBuilder: (ctx, index) {
                    final g = _groups[index] as Map<String, dynamic>;
                    final name = g['name']?.toString() ?? 'Group';
                    final description = g['description']?.toString();
                    final type = g['type']?.toString() ?? 'group';
                    final members = (g['members'] as List?) ?? const [];

                    return Container(
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(16),
                        boxShadow: [
                          BoxShadow(
                            color: theme.colorScheme.shadow.withValues(
                              alpha: 26,
                            ),
                            blurRadius: 8,
                            offset: const Offset(0, 2),
                          ),
                        ],
                      ),
                      child: Material(
                        color: theme.colorScheme.surface,
                        borderRadius: BorderRadius.circular(16),
                        child: InkWell(
                          onTap: () => _openGroupDetail(g),
                          borderRadius: BorderRadius.circular(16),
                          child: Padding(
                            padding: const EdgeInsets.all(20.0),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    Container(
                                      padding: const EdgeInsets.all(12),
                                      decoration: BoxDecoration(
                                        color: theme.colorScheme.primary
                                            .withValues(alpha: 26),
                                        borderRadius: BorderRadius.circular(12),
                                      ),
                                      child: Icon(
                                        type == 'family'
                                            ? Icons.family_restroom
                                            : Icons.groups,
                                        color: theme.colorScheme.primary,
                                        size: 24,
                                      ),
                                    ),
                                    const SizedBox(width: 16),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment:
                                            CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            name,
                                            style: theme.textTheme.titleMedium
                                                ?.copyWith(
                                                  fontWeight: FontWeight.bold,
                                                  color: theme
                                                      .colorScheme
                                                      .onSurface,
                                                ),
                                          ),
                                          const SizedBox(height: 4),
                                          Container(
                                            padding: const EdgeInsets.symmetric(
                                              horizontal: 8,
                                              vertical: 4,
                                            ),
                                            decoration: BoxDecoration(
                                              color: theme.colorScheme.secondary
                                                  .withValues(alpha: 26),
                                              borderRadius:
                                                  BorderRadius.circular(8),
                                            ),
                                            child: Text(
                                              '${type[0].toUpperCase()}${type.substring(1)} • ${members.length} members',
                                              style: theme.textTheme.bodySmall
                                                  ?.copyWith(
                                                    color: theme
                                                        .colorScheme
                                                        .onSecondary,
                                                    fontWeight: FontWeight.w500,
                                                  ),
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                    if (_isCurrentUserAdmin(g))
                                      IconButton(
                                        onPressed: () =>
                                            _deleteGroup(g['id'] as int),
                                        icon: Icon(
                                          Icons.delete_outline,
                                          color: theme.colorScheme.error,
                                        ),
                                        tooltip: 'Delete Group',
                                      ),
                                  ],
                                ),
                                if (description != null &&
                                    description.isNotEmpty) ...[
                                  const SizedBox(height: 12),
                                  Text(
                                    description,
                                    maxLines: 2,
                                    overflow: TextOverflow.ellipsis,
                                    style: theme.textTheme.bodyMedium?.copyWith(
                                      color: theme.colorScheme.onSurface
                                          .withValues(alpha: 179),
                                    ),
                                  ),
                                ],
                              ],
                            ),
                          ),
                        ),
                      ),
                    );
                  },
                  separatorBuilder: (_, __) => const SizedBox(height: 16),
                  itemCount: _groups.length,
                ),
        ),
      ),
    );
  }
}

class EditGroupScreen extends StatefulWidget {
  final Map<String, dynamic>? initialGroup;

  const EditGroupScreen({super.key, this.initialGroup});

  @override
  State<EditGroupScreen> createState() => _EditGroupScreenState();
}

class _EditGroupScreenState extends State<EditGroupScreen> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _nameCtrl;
  late TextEditingController _descriptionCtrl;
  late TextEditingController _budgetCtrl;

  String _type = 'friends';
  bool _saving = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    final g = widget.initialGroup;
    _nameCtrl = TextEditingController(
      text: g != null ? g['name']?.toString() : '',
    );
    _descriptionCtrl = TextEditingController(
      text: g != null ? g['description']?.toString() : '',
    );
    _budgetCtrl = TextEditingController(
      text: g != null && g['budget_limit'] != null
          ? g['budget_limit'].toString()
          : '',
    );
    if (g != null && g['type'] != null) {
      _type = g['type'].toString();
    }
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _descriptionCtrl.dispose();
    _budgetCtrl.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;

    final budgetText = _budgetCtrl.text.trim();
    double? budget;
    if (budgetText.isNotEmpty) {
      budget = double.tryParse(budgetText);
    }

    setState(() {
      _saving = true;
      _error = null;
    });

    try {
      if (widget.initialGroup == null) {
        await ApiService.createGroupApi(
          name: _nameCtrl.text.trim(),
          type: _type,
          description: _descriptionCtrl.text.trim().isEmpty
              ? null
              : _descriptionCtrl.text.trim(),
          budgetLimit: budget,
        );
      } else {
        await ApiService.updateGroupApi(
          id: widget.initialGroup!['id'] as int,
          name: _nameCtrl.text.trim(),
          description: _descriptionCtrl.text.trim(),
        );
      }

      if (!mounted) return;
      Navigator.pop(context, true);
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      if (mounted) {
        setState(() => _saving = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isEdit = widget.initialGroup != null;

    return Scaffold(
      appBar: AppBar(
        title: Text(isEdit ? 'Edit Group' : 'Create Group'),
        backgroundColor: theme.colorScheme.primary,
        foregroundColor: theme.colorScheme.onPrimary,
      ),
      body: Container(
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: theme.brightness == Brightness.light
                ? [
                    theme.colorScheme.surface,
                    theme.colorScheme.surfaceContainerLowest,
                  ]
                : [
                    theme.colorScheme.surface,
                    theme.colorScheme.surfaceContainerHighest.withValues(
                      alpha: 77,
                    ),
                  ],
          ),
        ),
        child: SafeArea(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(20.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Header Card
                Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(16),
                    gradient: LinearGradient(
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                      colors: [
                        theme.colorScheme.primary.withValues(alpha: 26),
                        theme.colorScheme.primary.withValues(alpha: 13),
                      ],
                    ),
                    border: Border.all(
                      color: theme.colorScheme.primary.withValues(alpha: 51),
                    ),
                  ),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: theme.colorScheme.primary.withValues(
                            alpha: 51,
                          ),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Icon(
                          isEdit ? Icons.edit : Icons.group_add,
                          color: theme.colorScheme.primary,
                          size: 24,
                        ),
                      ),
                      const SizedBox(width: 16),
                      Text(
                        isEdit ? 'Edit Group' : 'Create New Group',
                        style: theme.textTheme.titleLarge?.copyWith(
                          fontWeight: FontWeight.bold,
                          color: theme.colorScheme.onSurface,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 24),

                // Error Message
                if (_error != null) ...[
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: theme.colorScheme.error.withValues(alpha: 26),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(
                        color: theme.colorScheme.error.withValues(alpha: 51),
                      ),
                    ),
                    child: Row(
                      children: [
                        Icon(Icons.error, color: theme.colorScheme.error),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Text(
                            _error!,
                            style: theme.textTheme.bodyMedium?.copyWith(
                              color: theme.colorScheme.error,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),
                ],

                // Form
                Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: theme.colorScheme.surface,
                    borderRadius: BorderRadius.circular(16),
                    boxShadow: [
                      BoxShadow(
                        color: theme.colorScheme.shadow.withValues(alpha: 26),
                        blurRadius: 8,
                        offset: const Offset(0, 2),
                      ),
                    ],
                  ),
                  child: Form(
                    key: _formKey,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Group Name
                        _ModernFormField(
                          label: 'Group Name',
                          required: true,
                          child: TextFormField(
                            controller: _nameCtrl,
                            decoration: InputDecoration(
                              border: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(12),
                              ),
                              hintText: 'Enter group name',
                              filled: true,
                              fillColor: theme.colorScheme.surface,
                              prefixIcon: Icon(
                                Icons.group,
                                color: theme.colorScheme.onSurface.withValues(
                                  alpha: 153,
                                ),
                              ),
                            ),
                            validator: (v) {
                              if (v == null || v.trim().isEmpty) {
                                return 'Please enter a group name';
                              }
                              return null;
                            },
                          ),
                        ),

                        // Description
                        _ModernFormField(
                          label: 'Description',
                          child: TextFormField(
                            controller: _descriptionCtrl,
                            maxLines: 3,
                            decoration: InputDecoration(
                              border: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(12),
                              ),
                              hintText: 'Describe your group (optional)',
                              filled: true,
                              fillColor: theme.colorScheme.surface,
                              prefixIcon: Padding(
                                padding: const EdgeInsets.only(bottom: 32),
                                child: Icon(
                                  Icons.description,
                                  color: theme.colorScheme.onSurface.withValues(
                                    alpha: 153,
                                  ),
                                ),
                              ),
                            ),
                          ),
                        ),

                        // Type
                        _ModernFormField(
                          label: 'Group Type',
                          required: true,
                          child: Container(
                            decoration: BoxDecoration(
                              color: theme.colorScheme.surface,
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(
                                color: theme.colorScheme.outline.withValues(
                                  alpha: 77,
                                ),
                              ),
                            ),
                            child: DropdownButtonFormField<String>(
                              value: _type,
                              decoration: const InputDecoration(
                                border: InputBorder.none,
                                contentPadding: EdgeInsets.symmetric(
                                  horizontal: 16,
                                  vertical: 12,
                                ),
                              ),
                              icon: Icon(
                                Icons.arrow_drop_down,
                                color: theme.colorScheme.onSurface.withValues(
                                  alpha: 153,
                                ),
                              ),
                              items: const [
                                DropdownMenuItem(
                                  value: 'family',
                                  child: Row(
                                    children: [
                                      Icon(
                                        Icons.family_restroom,
                                        color: Colors.blue,
                                      ),
                                      SizedBox(width: 12),
                                      Text('Family'),
                                    ],
                                  ),
                                ),
                                DropdownMenuItem(
                                  value: 'friends',
                                  child: Row(
                                    children: [
                                      Icon(Icons.groups, color: Colors.green),
                                      SizedBox(width: 12),
                                      Text('Friends'),
                                    ],
                                  ),
                                ),
                              ],
                              onChanged: (v) {
                                if (v == null) return;
                                setState(() => _type = v);
                              },
                              validator: (v) {
                                if (v == null || v.isEmpty) {
                                  return 'Please select a group type';
                                }
                                return null;
                              },
                            ),
                          ),
                        ),

                        // Budget Limit
                        _ModernFormField(
                          label: 'Budget Limit (optional)',
                          child: TextFormField(
                            controller: _budgetCtrl,
                            keyboardType: const TextInputType.numberWithOptions(
                              decimal: true,
                            ),
                            decoration: InputDecoration(
                              border: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(12),
                              ),
                              hintText: 'Set a spending limit',
                              filled: true,
                              fillColor: theme.colorScheme.surface,
                              prefixIcon: Icon(
                                Icons.account_balance_wallet,
                                color: theme.colorScheme.onSurface.withValues(
                                  alpha: 153,
                                ),
                              ),
                              suffixText: 'USD',
                            ),
                          ),
                        ),

                        const SizedBox(height: 32),

                        // Save Button
                        SizedBox(
                          width: double.infinity,
                          height: 56,
                          child: ElevatedButton(
                            onPressed: _saving ? null : _save,
                            style: ElevatedButton.styleFrom(
                              backgroundColor: theme.colorScheme.primary,
                              foregroundColor: theme.colorScheme.onPrimary,
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(16),
                              ),
                              elevation: 4,
                            ),
                            child: _saving
                                ? const SizedBox(
                                    width: 24,
                                    height: 24,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                      valueColor: AlwaysStoppedAnimation<Color>(
                                        Colors.white,
                                      ),
                                    ),
                                  )
                                : Row(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                      Icon(
                                        isEdit ? Icons.save : Icons.group_add,
                                      ),
                                      const SizedBox(width: 8),
                                      Text(
                                        isEdit
                                            ? 'Save Changes'
                                            : 'Create Group',
                                        style: const TextStyle(
                                          fontSize: 16,
                                          fontWeight: FontWeight.bold,
                                        ),
                                      ),
                                    ],
                                  ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class GroupDetailScreen extends StatefulWidget {
  final int groupId;

  const GroupDetailScreen({super.key, required this.groupId});

  @override
  State<GroupDetailScreen> createState() => _GroupDetailScreenState();
}

class _GroupDetailScreenState extends State<GroupDetailScreen> {
  bool _loading = true;
  String? _error;
  Map<String, dynamic>? _group;
  List<dynamic> _members = [];
  List<dynamic> _transactions = [];

  double _totalIncome = 0;
  double _totalExpense = 0;
  int _transactionCount = 0;
  String? _lastActivity;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final group = await ApiService.fetchGroupApi(widget.groupId);
      final members = await ApiService.fetchGroupMembersApi(widget.groupId);
      final txs = await ApiService.fetchGroupTransactionsApi(widget.groupId);
      setState(() {
        _group = group;
        _members = members;
        _transactions = txs;
        _recalculateSummary();
      });
    } catch (e) {
      setState(() {
        _error = e.toString();
      });
    } finally {
      setState(() {
        _loading = false;
      });
    }
  }

  void _recalculateSummary() {
    double income = 0;
    double expense = 0;
    int count = 0;
    String? lastDate;

    for (final t in _transactions) {
      if (t is! Map) continue;
      final type = t['type']?.toString();
      final amountRaw = t['amount'];
      double amount;
      if (amountRaw is num) {
        amount = amountRaw.toDouble();
      } else if (amountRaw is String) {
        amount = double.tryParse(amountRaw) ?? 0;
      } else {
        amount = 0;
      }

      if (type == 'income') {
        income += amount;
      } else if (type == 'expense') {
        expense += amount;
      }

      final dateStr = t['transaction_date']?.toString();
      if (dateStr != null && dateStr.isNotEmpty) {
        if (lastDate == null || dateStr.compareTo(lastDate) > 0) {
          lastDate = dateStr;
        }
      }

      count++;
    }

    _totalIncome = income;
    _totalExpense = expense;
    _transactionCount = count;
    _lastActivity = lastDate;
  }

  Future<void> _inviteMember() async {
    final nameCtrl = TextEditingController();
    final emailCtrl = TextEditingController();
    final phoneCtrl = TextEditingController();

    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) {
        final theme = Theme.of(ctx);
        return Dialog(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(20),
          ),
          child: Container(
            constraints: const BoxConstraints(maxWidth: 400),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                // Modern Header with Gradient
                Container(
                  padding: const EdgeInsets.all(24),
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                      colors: [
                        theme.colorScheme.secondary,
                        theme.colorScheme.secondary.withValues(alpha: 204),
                      ],
                    ),
                    borderRadius: const BorderRadius.only(
                      topLeft: Radius.circular(20),
                      topRight: Radius.circular(20),
                    ),
                  ),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: theme.colorScheme.onPrimary.withValues(
                            alpha: 51,
                          ),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Icon(
                          Icons.person_add,
                          color: theme.colorScheme.onSecondary,
                          size: 24,
                        ),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Invite Member',
                              style: theme.textTheme.titleLarge?.copyWith(
                                color: theme.colorScheme.onSecondary,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                            Text(
                              'Add someone to your expense sharing group',
                              style: theme.textTheme.bodyMedium?.copyWith(
                                color: theme.colorScheme.onSecondary.withValues(
                                  alpha: 204,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                      IconButton(
                        onPressed: () => Navigator.pop(ctx, false),
                        icon: Icon(
                          Icons.close,
                          color: theme.colorScheme.onSecondary,
                        ),
                      ),
                    ],
                  ),
                ),

                // Form Content
                Container(
                  padding: const EdgeInsets.all(24),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      _ModernFormField(
                        label: 'Full Name',
                        required: true,
                        child: TextFormField(
                          controller: nameCtrl,
                          decoration: InputDecoration(
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                            hintText: 'Enter full name',
                            filled: true,
                            fillColor: theme.colorScheme.surface,
                            prefixIcon: Icon(
                              Icons.person,
                              color: theme.colorScheme.onSurface.withValues(
                                alpha: 153,
                              ),
                            ),
                          ),
                          validator: (value) {
                            if (value == null || value.trim().isEmpty) {
                              return 'Please enter a name';
                            }
                            return null;
                          },
                        ),
                      ),

                      _ModernFormField(
                        label: 'Email Address',
                        required: true,
                        child: TextFormField(
                          controller: emailCtrl,
                          keyboardType: TextInputType.emailAddress,
                          decoration: InputDecoration(
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                            hintText: 'Enter email address',
                            filled: true,
                            fillColor: theme.colorScheme.surface,
                            prefixIcon: Icon(
                              Icons.email,
                              color: theme.colorScheme.onSurface.withValues(
                                alpha: 153,
                              ),
                            ),
                          ),
                          validator: (value) {
                            if (value == null || value.trim().isEmpty) {
                              return 'Please enter an email';
                            }
                            if (!RegExp(
                              r'^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$',
                            ).hasMatch(value)) {
                              return 'Please enter a valid email';
                            }
                            return null;
                          },
                        ),
                      ),

                      _ModernFormField(
                        label: 'Phone Number (Optional)',
                        child: TextFormField(
                          controller: phoneCtrl,
                          keyboardType: TextInputType.phone,
                          decoration: InputDecoration(
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                            hintText: 'Enter phone number',
                            filled: true,
                            fillColor: theme.colorScheme.surface,
                            prefixIcon: Icon(
                              Icons.phone,
                              color: theme.colorScheme.onSurface.withValues(
                                alpha: 153,
                              ),
                            ),
                          ),
                        ),
                      ),

                      const SizedBox(height: 24),

                      // Action Buttons
                      Row(
                        children: [
                          Expanded(
                            child: OutlinedButton(
                              onPressed: () => Navigator.pop(ctx, false),
                              style: OutlinedButton.styleFrom(
                                padding: const EdgeInsets.symmetric(
                                  vertical: 12,
                                ),
                                side: BorderSide(
                                  color: theme.colorScheme.outline,
                                ),
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(12),
                                ),
                              ),
                              child: Text(
                                'Cancel',
                                style: TextStyle(
                                  color: theme.colorScheme.onSurface,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: ElevatedButton(
                              onPressed: () => Navigator.pop(ctx, true),
                              style: ElevatedButton.styleFrom(
                                padding: const EdgeInsets.symmetric(
                                  vertical: 12,
                                ),
                                backgroundColor: theme.colorScheme.primary,
                                foregroundColor: theme.colorScheme.onPrimary,
                                elevation: 0,
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(12),
                                ),
                              ),
                              child: const Text(
                                'Send Invite',
                                style: TextStyle(fontWeight: FontWeight.w600),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );

    if (ok != true) return;

    try {
      await ApiService.inviteGroupMemberApi(
        groupId: widget.groupId,
        name: nameCtrl.text.trim(),
        email: emailCtrl.text.trim(),
        phone: phoneCtrl.text.trim(),
      );
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Invitation sent')));
      _load();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Failed to invite member: $e')));
    }
  }

  Future<void> _removeMember(int memberId) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Remove member'),
        content: const Text(
          'Are you sure you want to remove this member from the group?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Remove'),
          ),
        ],
      ),
    );

    if (confirm != true) return;

    try {
      await ApiService.removeGroupMemberApi(
        groupId: widget.groupId,
        memberId: memberId,
      );
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Member removed')));
      _load();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Failed to remove member: $e')));
    }
  }

  Future<void> _openSplitExpense() async {
    final created = await Navigator.push<bool>(
      context,
      MaterialPageRoute(
        builder: (_) => GroupSplitExpenseScreen(groupId: widget.groupId),
      ),
    );
    if (created == true) {
      _load();
    }
  }

  bool _isCurrentUserAdmin() {
    if (_group == null) return false;

    final currentUser = ApiService.currentUser;
    if (currentUser == null) return false;

    final currentUserId = currentUser['id'];

    // Check if current user is the owner
    if (_group!['owner_id'] == currentUserId) return true;

    // Check if current user has admin role in members
    final members = _members;
    for (final member in members) {
      if (member['user_id'] == currentUserId && member['role'] == 'admin') {
        return true;
      }
    }

    return false;
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: Text(_group?['name']?.toString() ?? 'Group'),
        backgroundColor: theme.colorScheme.secondary,
        foregroundColor: theme.colorScheme.onSecondary,
        actions: [
          IconButton(onPressed: _load, icon: const Icon(Icons.refresh)),
          if (_isCurrentUserAdmin())
            IconButton(
              onPressed: _inviteMember,
              icon: const Icon(Icons.person_add_alt_1),
            ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _openSplitExpense,
        icon: const Icon(Icons.call_split),
        label: const Text('Split expense'),
        backgroundColor: theme.colorScheme.secondary,
        foregroundColor: theme.colorScheme.onSecondary,
        elevation: 4,
      ),
      body: Container(
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: theme.brightness == Brightness.light
                ? [
                    theme.colorScheme.surface,
                    theme.colorScheme.surfaceContainerLowest,
                  ]
                : [
                    theme.colorScheme.surface,
                    theme.colorScheme.surfaceContainerHighest.withValues(
                      alpha: 77,
                    ),
                  ],
          ),
        ),
        child: _loading
            ? const Center(child: CircularProgressIndicator())
            : _error != null
            ? Center(
                child: Padding(
                  padding: const EdgeInsets.all(20.0),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Container(
                        padding: const EdgeInsets.all(20),
                        decoration: BoxDecoration(
                          color: theme.colorScheme.error.withValues(alpha: 26),
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(
                            color: theme.colorScheme.error.withValues(
                              alpha: 51,
                            ),
                          ),
                        ),
                        child: Icon(
                          Icons.error_outline,
                          color: theme.colorScheme.error,
                          size: 48,
                        ),
                      ),
                      const SizedBox(height: 16),
                      Text(
                        'Failed to load group details',
                        textAlign: TextAlign.center,
                        style: theme.textTheme.titleMedium?.copyWith(
                          color: theme.colorScheme.error,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        _error!,
                        textAlign: TextAlign.center,
                        style: theme.textTheme.bodyMedium?.copyWith(
                          color: theme.colorScheme.onSurface.withValues(
                            alpha: 179,
                          ),
                        ),
                      ),
                      const SizedBox(height: 20),
                      ElevatedButton.icon(
                        onPressed: _load,
                        icon: const Icon(Icons.refresh),
                        label: const Text('Try Again'),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: theme.colorScheme.primary,
                          foregroundColor: theme.colorScheme.onPrimary,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              )
            : RefreshIndicator(
                onRefresh: _load,
                child: ListView(
                  padding: const EdgeInsets.all(20.0),
                  children: [
                    if (_group?['description'] != null &&
                        _group!['description'].toString().isNotEmpty) ...[
                      Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: theme.colorScheme.surface,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(
                            color: theme.colorScheme.outline.withValues(
                              alpha: 51,
                            ),
                          ),
                        ),
                        child: Text(
                          _group!['description'].toString(),
                          style: theme.textTheme.bodyMedium?.copyWith(
                            color: theme.colorScheme.onSurface,
                          ),
                        ),
                      ),
                      const SizedBox(height: 20),
                    ],
                    // Summary Card with Modern Styling
                    Container(
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(16),
                        boxShadow: [
                          BoxShadow(
                            color: theme.colorScheme.shadow.withValues(
                              alpha: 26,
                            ),
                            blurRadius: 8,
                            offset: const Offset(0, 2),
                          ),
                        ],
                      ),
                      child: Material(
                        color: theme.colorScheme.surface,
                        borderRadius: BorderRadius.circular(16),
                        child: Padding(
                          padding: const EdgeInsets.all(20.0),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  Container(
                                    padding: const EdgeInsets.all(10),
                                    decoration: BoxDecoration(
                                      color: theme.colorScheme.primary
                                          .withValues(alpha: 26),
                                      borderRadius: BorderRadius.circular(10),
                                    ),
                                    child: Icon(
                                      Icons.analytics,
                                      color: theme.colorScheme.primary,
                                      size: 20,
                                    ),
                                  ),
                                  const SizedBox(width: 12),
                                  Text(
                                    'Summary',
                                    style: theme.textTheme.titleMedium
                                        ?.copyWith(
                                          fontWeight: FontWeight.bold,
                                          color: theme.colorScheme.onSurface,
                                        ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 16),
                              Wrap(
                                spacing: 16,
                                runSpacing: 8,
                                children: [
                                  Container(
                                    padding: const EdgeInsets.symmetric(
                                      horizontal: 12,
                                      vertical: 6,
                                    ),
                                    decoration: BoxDecoration(
                                      color: theme.colorScheme.secondary
                                          .withValues(alpha: 26),
                                      borderRadius: BorderRadius.circular(8),
                                    ),
                                    child: Text(
                                      'Transactions: $_transactionCount',
                                      style: theme.textTheme.bodyMedium
                                          ?.copyWith(
                                            color:
                                                theme.colorScheme.onSecondary,
                                            fontWeight: FontWeight.w500,
                                          ),
                                    ),
                                  ),
                                  if (_group?['budget_limit'] != null)
                                    Container(
                                      padding: const EdgeInsets.symmetric(
                                        horizontal: 12,
                                        vertical: 6,
                                      ),
                                      decoration: BoxDecoration(
                                        color: theme.colorScheme.tertiary
                                            .withValues(alpha: 26),
                                        borderRadius: BorderRadius.circular(8),
                                      ),
                                      child: Text(
                                        'Budget limit: ${_group!['budget_limit']}',
                                        style: theme.textTheme.bodyMedium
                                            ?.copyWith(
                                              color:
                                                  theme.colorScheme.onTertiary,
                                              fontWeight: FontWeight.w500,
                                            ),
                                      ),
                                    ),
                                ],
                              ),
                              const SizedBox(height: 16),
                              Row(
                                mainAxisAlignment:
                                    MainAxisAlignment.spaceBetween,
                                children: [
                                  _buildSummaryColumn(
                                    'Income',
                                    _totalIncome.toStringAsFixed(2),
                                    theme.colorScheme.primary,
                                    theme,
                                  ),
                                  _buildSummaryColumn(
                                    'Expense',
                                    _totalExpense.toStringAsFixed(2),
                                    theme.colorScheme.error,
                                    theme,
                                  ),
                                  _buildSummaryColumn(
                                    'Net',
                                    (_totalIncome - _totalExpense)
                                        .toStringAsFixed(2),
                                    (_totalIncome - _totalExpense) >= 0
                                        ? theme.colorScheme.primary
                                        : theme.colorScheme.error,
                                    theme,
                                  ),
                                ],
                              ),
                              if (_lastActivity != null) ...[
                                const SizedBox(height: 16),
                                Container(
                                  padding: const EdgeInsets.all(12),
                                  decoration: BoxDecoration(
                                    color: theme
                                        .colorScheme
                                        .surfaceContainerHighest
                                        .withValues(alpha: 128),
                                    borderRadius: BorderRadius.circular(8),
                                  ),
                                  child: Row(
                                    children: [
                                      Icon(
                                        Icons.access_time,
                                        size: 16,
                                        color: theme.colorScheme.onSurface
                                            .withValues(alpha: 179),
                                      ),
                                      const SizedBox(width: 8),
                                      Text(
                                        'Last activity: $_lastActivity',
                                        style: theme.textTheme.bodySmall
                                            ?.copyWith(
                                              color: theme.colorScheme.onSurface
                                                  .withValues(alpha: 179),
                                            ),
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                            ],
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(height: 24),
                    // Members Section
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: theme.colorScheme.primary.withValues(
                              alpha: 26,
                            ),
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: Icon(
                            Icons.group,
                            color: theme.colorScheme.primary,
                            size: 20,
                          ),
                        ),
                        const SizedBox(width: 12),
                        Text(
                          'Members',
                          style: theme.textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.bold,
                            color: theme.colorScheme.onSurface,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    if (_members.isEmpty)
                      Container(
                        padding: const EdgeInsets.all(20),
                        decoration: BoxDecoration(
                          color: theme.colorScheme.surface,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(
                            color: theme.colorScheme.outline.withValues(
                              alpha: 51,
                            ),
                          ),
                        ),
                        child: Column(
                          children: [
                            Icon(
                              Icons.people_outline,
                              size: 48,
                              color: theme.colorScheme.onSurface.withValues(
                                alpha: 128,
                              ),
                            ),
                            const SizedBox(height: 12),
                            Text(
                              'No members yet',
                              style: theme.textTheme.titleMedium?.copyWith(
                                color: theme.colorScheme.onSurface,
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              'Use the + button to invite someone.',
                              textAlign: TextAlign.center,
                              style: theme.textTheme.bodyMedium?.copyWith(
                                color: theme.colorScheme.onSurface.withOpacity(
                                  0.7,
                                ),
                              ),
                            ),
                          ],
                        ),
                      )
                    else
                      ..._members.map((m) {
                        final user = m['user'] as Map<String, dynamic>?;
                        final name = user?['name']?.toString() ?? 'Member';
                        final email = user?['email']?.toString() ?? '';
                        final role = m['role']?.toString() ?? '';
                        final imageUrl = user?['avatar']?.toString();
                        final hasValidImage =
                            imageUrl != null && imageUrl.startsWith('http');
                        return Container(
                          margin: const EdgeInsets.only(bottom: 8),
                          decoration: BoxDecoration(
                            color: theme.colorScheme.surface,
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(
                              color: theme.colorScheme.outline.withValues(
                                alpha: 51,
                              ),
                            ),
                          ),
                          child: ListTile(
                            contentPadding: const EdgeInsets.all(16),
                            leading: CircleAvatar(
                              radius: 20,
                              backgroundColor:
                                  theme.colorScheme.secondaryContainer,
                              backgroundImage: hasValidImage
                                  ? NetworkImage(imageUrl!)
                                  : null,
                              child: Icon(
                                Icons.person,
                                size: 20,
                                color: theme.colorScheme.secondary,
                              ),
                            ),
                            title: Text(
                              name,
                              style: theme.textTheme.titleSmall?.copyWith(
                                fontWeight: FontWeight.w600,
                                color: theme.colorScheme.onSurface,
                              ),
                            ),
                            subtitle: Text(
                              '$email${role.isNotEmpty ? ' • $role' : ''}',
                              style: theme.textTheme.bodySmall?.copyWith(
                                color: theme.colorScheme.onSurface.withOpacity(
                                  0.7,
                                ),
                              ),
                            ),
                            trailing: _isCurrentUserAdmin()
                                ? IconButton(
                                    icon: Icon(
                                      Icons.remove_circle_outline,
                                      color: theme.colorScheme.error,
                                    ),
                                    onPressed: () =>
                                        _removeMember(m['id'] as int),
                                  )
                                : null,
                          ),
                        );
                      }),
                    const SizedBox(height: 32),
                    // Transactions Section
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: theme.colorScheme.primary.withOpacity(0.1),
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: Icon(
                            Icons.receipt_long,
                            color: theme.colorScheme.primary,
                            size: 20,
                          ),
                        ),
                        const SizedBox(width: 12),
                        Text(
                          'Recent group transactions',
                          style: theme.textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.bold,
                            color: theme.colorScheme.onSurface,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),

                    if (_transactions.isEmpty)
                      Container(
                        padding: const EdgeInsets.all(20),
                        decoration: BoxDecoration(
                          color: theme.colorScheme.surface,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(
                            color: theme.colorScheme.outline.withOpacity(0.2),
                          ),
                        ),
                        child: Column(
                          children: [
                            Icon(
                              Icons.receipt_outlined,
                              size: 48,
                              color: theme.colorScheme.onSurface.withOpacity(
                                0.5,
                              ),
                            ),
                            const SizedBox(height: 12),
                            Text(
                              'No transactions yet',
                              style: theme.textTheme.titleMedium?.copyWith(
                                color: theme.colorScheme.onSurface,
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              'Group transactions will appear here.',
                              textAlign: TextAlign.center,
                              style: theme.textTheme.bodyMedium?.copyWith(
                                color: theme.colorScheme.onSurface.withOpacity(
                                  0.7,
                                ),
                              ),
                            ),
                          ],
                        ),
                      )
                    else
                      ..._transactions.map((t) {
                        final desc = t['description']?.toString() ?? '';
                        final amount = t['amount'];
                        final date = t['transaction_date']?.toString() ?? '';
                        final type = t['type']?.toString() ?? 'expense';
                        return Container(
                          margin: const EdgeInsets.only(bottom: 8),
                          decoration: BoxDecoration(
                            color: theme.colorScheme.surface,
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(
                              color: theme.colorScheme.outline.withOpacity(0.2),
                            ),
                          ),
                          child: ListTile(
                            contentPadding: const EdgeInsets.all(16),
                            title: Text(
                              desc.isEmpty ? 'Expense' : desc,
                              style: theme.textTheme.titleSmall?.copyWith(
                                fontWeight: FontWeight.w600,
                                color: theme.colorScheme.onSurface,
                              ),
                            ),
                            subtitle: Text(
                              date,
                              style: theme.textTheme.bodySmall?.copyWith(
                                color: theme.colorScheme.onSurface.withOpacity(
                                  0.7,
                                ),
                              ),
                            ),
                            trailing: Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 12,
                                vertical: 6,
                              ),
                              decoration: BoxDecoration(
                                color: type == 'income'
                                    ? theme.colorScheme.primary.withOpacity(0.1)
                                    : theme.colorScheme.error.withOpacity(0.1),
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Text(
                                amount?.toString() ?? '',
                                style: theme.textTheme.bodyMedium?.copyWith(
                                  color: type == 'income'
                                      ? theme.colorScheme.primary
                                      : theme.colorScheme.error,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ),
                          ),
                        );
                      }),
                  ],
                ),
              ),
      ),
    );
  }

  Widget _buildSummaryColumn(
    String label,
    String value,
    Color color,
    ThemeData theme,
  ) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: theme.textTheme.bodySmall?.copyWith(
            color: theme.colorScheme.onSurface.withOpacity(0.7),
          ),
        ),
        const SizedBox(height: 4),
        Text(
          value,
          style: theme.textTheme.titleSmall?.copyWith(
            color: color,
            fontWeight: FontWeight.bold,
          ),
        ),
      ],
    );
  }
}

class GroupSplitExpenseScreen extends StatefulWidget {
  final int groupId;

  const GroupSplitExpenseScreen({super.key, required this.groupId});

  @override
  State<GroupSplitExpenseScreen> createState() =>
      _GroupSplitExpenseScreenState();
}

class _GroupSplitExpenseScreenState extends State<GroupSplitExpenseScreen> {
  final _formKey = GlobalKey<FormState>();

  final TextEditingController _amountCtrl = TextEditingController();
  final TextEditingController _descriptionCtrl = TextEditingController();

  String _type = 'expense';
  String _splitType = 'equal';
  bool _loadingMembers = true;
  bool _saving = false;
  String? _error;
  List<dynamic> _members = [];

  // Per-member controllers for custom/percentage splits
  final List<TextEditingController> _amountCtrls = [];
  final List<TextEditingController> _percentCtrls = [];

  // Category support
  List<dynamic> _categories = [];
  dynamic _selectedCategory;
  bool _loadingCategories = false;

  // Optional receipt image
  XFile? _receiptImage;
  final ImagePicker _imagePicker = ImagePicker();

  @override
  void initState() {
    super.initState();
    _loadMembers();
    _loadCategories();
  }

  Future<void> _loadMembers() async {
    setState(() {
      _loadingMembers = true;
      _error = null;
    });

    try {
      final members = await ApiService.fetchGroupMembersApi(widget.groupId);
      setState(() {
        _members = members;
        _amountCtrls
          ..clear()
          ..addAll(
            List.generate(members.length, (_) => TextEditingController()),
          );
        _percentCtrls
          ..clear()
          ..addAll(
            List.generate(members.length, (_) => TextEditingController()),
          );
      });
    } catch (e) {
      setState(() {
        _error = e.toString();
      });
    } finally {
      setState(() {
        _loadingMembers = false;
      });
    }
  }

  Future<void> _loadCategories() async {
    setState(() {
      _loadingCategories = true;
    });
    try {
      final cats = await ApiService.fetchCategories(type: _type);
      setState(() {
        _categories = cats;
        if (_categories.isNotEmpty) {
          _selectedCategory = _categories.first;
        }
      });
    } catch (e) {
      setState(() {
        _error ??= e.toString();
      });
    } finally {
      setState(() {
        _loadingCategories = false;
      });
    }
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;

    final amountText = _amountCtrl.text.trim();
    final amount = amountText.isEmpty ? null : double.tryParse(amountText);
    if ((amount == null || amount <= 0) && _receiptImage == null) {
      setState(() => _error = 'Enter a valid amount or attach a bill image.');
      return;
    }

    if (_members.isEmpty) {
      setState(() => _error = 'This group has no members to split with.');
      return;
    }

    final List<Map<String, dynamic>> splits = [];

    if (_splitType == 'equal') {
      for (final m in _members) {
        final userId = m['user_id'] ?? (m['user']?['id']);
        if (userId != null) {
          splits.add({'user_id': userId});
        }
      }
    } else if (_splitType == 'custom') {
      double totalCustom = 0;
      for (int i = 0; i < _members.length; i++) {
        final m = _members[i];
        final userId = m['user_id'] ?? (m['user']?['id']);
        if (userId == null) continue;
        final text = _amountCtrls[i].text.trim();
        if (text.isEmpty) continue;
        final v = double.tryParse(text);
        if (v == null || v < 0) {
          setState(() => _error = 'Invalid amount for one of the members.');
          return;
        }
        totalCustom += v;
        splits.add({'user_id': userId, 'amount': v});
      }
      if (splits.isEmpty) {
        setState(
          () => _error = 'Please enter amounts for at least one member.',
        );
        return;
      }
      if (amount != null && (totalCustom - amount).abs() > 0.01) {
        setState(
          () => _error = 'Custom split amounts must add up to the total.',
        );
        return;
      }
    } else if (_splitType == 'percentage') {
      double totalPercent = 0;
      for (int i = 0; i < _members.length; i++) {
        final m = _members[i];
        final userId = m['user_id'] ?? (m['user']?['id']);
        if (userId == null) continue;
        final text = _percentCtrls[i].text.trim();
        if (text.isEmpty) continue;
        final v = double.tryParse(text);
        if (v == null || v < 0) {
          setState(() => _error = 'Invalid percentage for one of the members.');
          return;
        }
        totalPercent += v;
        splits.add({'user_id': userId, 'percent': v});
      }
      if (splits.isEmpty) {
        setState(
          () => _error =
              'Please enter percentage values for at least one member.',
        );
        return;
      }
      if ((totalPercent - 100).abs() > 0.01) {
        setState(() => _error = 'Percentages must add up to 100%.');
        return;
      }
    }

    if (splits.isEmpty) {
      setState(() => _error = 'Unable to determine group members for split.');
      return;
    }

    setState(() {
      _saving = true;
      _error = null;
    });

    try {
      int? categoryId;
      if (_selectedCategory != null) {
        categoryId = _selectedCategory['id'] as int?;
      }

      List<int>? receiptBytes;
      String? receiptFilename;
      if (_receiptImage != null) {
        final bytes = await _receiptImage!.readAsBytes();
        receiptBytes = bytes.toList();
        receiptFilename = _receiptImage!.name.isNotEmpty
            ? _receiptImage!.name
            : 'receipt_${DateTime.now().millisecondsSinceEpoch}.jpg';
      }

      await ApiService.splitGroupExpenseApi(
        groupId: widget.groupId,
        type: _type,
        amount: amount,
        description: _descriptionCtrl.text.trim(),
        splitType: _splitType,
        splits: splits,
        categoryId: categoryId,
        receiptBytes: receiptBytes,
        receiptFilename: receiptFilename,
      );

      if (!mounted) return;
      Navigator.pop(context, true);
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      if (mounted) {
        setState(() => _saving = false);
      }
    }
  }

  @override
  void dispose() {
    _amountCtrl.dispose();
    _descriptionCtrl.dispose();
    for (final c in _amountCtrls) {
      c.dispose();
    }
    for (final c in _percentCtrls) {
      c.dispose();
    }
    super.dispose();
  }

  Widget _buildSplitTypeChip(String value, String label) {
    final theme = Theme.of(context);
    final isSelected = _splitType == value;

    return FilterChip(
      label: Text(label),
      selected: isSelected,
      onSelected: (selected) {
        if (selected) {
          setState(() => _splitType = value);
        }
      },
      backgroundColor: theme.colorScheme.surfaceContainerHighest.withOpacity(
        0.3,
      ),
      selectedColor: theme.colorScheme.primaryContainer,
      checkmarkColor: theme.colorScheme.onPrimaryContainer,
      labelStyle: theme.textTheme.bodyMedium?.copyWith(
        color: isSelected
            ? theme.colorScheme.onPrimaryContainer
            : theme.colorScheme.onSurfaceVariant,
        fontWeight: isSelected ? FontWeight.w600 : FontWeight.normal,
      ),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(20),
        side: BorderSide(
          color: isSelected
              ? theme.colorScheme.primary
              : theme.colorScheme.outline.withOpacity(0.3),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Split expense'),
        backgroundColor: theme.colorScheme.secondary,
        foregroundColor: theme.colorScheme.onSecondary,
      ),
      body: _loadingMembers
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16.0),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (_error != null) ...[
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: theme.colorScheme.errorContainer,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(
                            color: theme.colorScheme.error.withOpacity(0.3),
                          ),
                        ),
                        child: Row(
                          children: [
                            Icon(
                              Icons.error_outline,
                              color: theme.colorScheme.error,
                              size: 20,
                            ),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                _error!,
                                style: theme.textTheme.bodyMedium?.copyWith(
                                  color: theme.colorScheme.error,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 16),
                    ],
                    _ModernFormField(
                      label: 'Total amount',
                      required: _receiptImage == null,
                      child: TextFormField(
                        controller: _amountCtrl,
                        keyboardType: const TextInputType.numberWithOptions(
                          decimal: true,
                        ),
                        style: theme.textTheme.bodyLarge,
                        decoration: InputDecoration(
                          hintText: 'Enter total amount',
                          prefixIcon: Icon(
                            Icons.attach_money,
                            color: theme.colorScheme.secondary,
                          ),
                          filled: true,
                          fillColor: theme.colorScheme.surfaceContainerHighest
                              .withOpacity(0.3),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide: BorderSide.none,
                          ),
                          enabledBorder: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide: BorderSide(
                              color: theme.colorScheme.outline.withOpacity(0.3),
                            ),
                          ),
                          focusedBorder: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide: BorderSide(
                              color: theme.colorScheme.secondary,
                              width: 2,
                            ),
                          ),
                        ),
                        validator: (v) {
                          // Allow empty amount if a receipt (bill image)
                          // is attached; backend will infer the total
                          // from OCR just like the web app.
                          if ((v == null || v.trim().isEmpty) &&
                              _receiptImage == null) {
                            return 'Enter the total amount or attach a bill image';
                          }
                          return null;
                        },
                      ),
                    ),
                    _ModernFormField(
                      label: 'Description',
                      child: TextFormField(
                        controller: _descriptionCtrl,
                        style: theme.textTheme.bodyLarge,
                        maxLines: 3,
                        decoration: InputDecoration(
                          hintText: 'What was this expense for?',
                          prefixIcon: Icon(
                            Icons.description,
                            color: theme.colorScheme.secondary,
                          ),
                          filled: true,
                          fillColor: theme.colorScheme.surfaceContainerHighest
                              .withOpacity(0.3),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide: BorderSide.none,
                          ),
                          enabledBorder: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide: BorderSide(
                              color: theme.colorScheme.outline.withOpacity(0.3),
                            ),
                          ),
                          focusedBorder: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide: BorderSide(
                              color: theme.colorScheme.secondary,
                              width: 2,
                            ),
                          ),
                        ),
                      ),
                    ),
                    _ModernFormField(
                      label: 'Type',
                      required: true,
                      child: Container(
                        decoration: BoxDecoration(
                          color: theme.colorScheme.surfaceContainerHighest
                              .withOpacity(0.3),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(
                            color: theme.colorScheme.outline.withOpacity(0.3),
                          ),
                        ),
                        child: Row(
                          children: [
                            Expanded(
                              child: RadioListTile<String>(
                                value: 'expense',
                                groupValue: _type,
                                onChanged: (v) {
                                  if (v == null) return;
                                  setState(() {
                                    _type = v;
                                  });
                                  _loadCategories();
                                },
                                title: Text(
                                  'Expense',
                                  style: TextStyle(
                                    color: theme.colorScheme.onSurfaceVariant,
                                  ),
                                ),
                                contentPadding: const EdgeInsets.symmetric(
                                  horizontal: 16,
                                  vertical: 8,
                                ),
                                dense: true,
                              ),
                            ),
                            Container(
                              width: 1,
                              height: 40,
                              color: theme.colorScheme.outline.withOpacity(0.3),
                            ),
                            Expanded(
                              child: RadioListTile<String>(
                                value: 'income',
                                groupValue: _type,
                                onChanged: (v) {
                                  if (v == null) return;
                                  setState(() => _type = v);
                                },
                                title: Text(
                                  'Income',
                                  style: TextStyle(
                                    color: theme.colorScheme.onSurfaceVariant,
                                  ),
                                ),
                                contentPadding: const EdgeInsets.symmetric(
                                  horizontal: 16,
                                  vertical: 8,
                                ),
                                dense: true,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                    _ModernFormField(
                      label: 'Split type',
                      required: true,
                      child: Wrap(
                        spacing: 8,
                        runSpacing: 8,
                        children: [
                          _buildSplitTypeChip('equal', 'Equal'),
                          _buildSplitTypeChip('custom', 'Custom'),
                          _buildSplitTypeChip('percentage', 'Percentage'),
                        ],
                      ),
                    ),
                    _ModernFormField(
                      label: 'Category',
                      child: _loadingCategories
                          ? Container(
                              padding: const EdgeInsets.symmetric(vertical: 12),
                              child: const LinearProgressIndicator(
                                minHeight: 2,
                              ),
                            )
                          : _categories.isEmpty
                          ? Container(
                              padding: const EdgeInsets.all(16),
                              decoration: BoxDecoration(
                                color: theme.colorScheme.surfaceContainerHighest
                                    .withOpacity(0.3),
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(
                                  color: theme.colorScheme.outline.withOpacity(
                                    0.3,
                                  ),
                                ),
                              ),
                              child: Row(
                                children: [
                                  Icon(
                                    Icons.category,
                                    color: theme.colorScheme.onSurfaceVariant,
                                  ),
                                  const SizedBox(width: 12),
                                  Text(
                                    'No categories available',
                                    style: theme.textTheme.bodyMedium?.copyWith(
                                      color: theme.colorScheme.onSurfaceVariant,
                                    ),
                                  ),
                                ],
                              ),
                            )
                          : Container(
                              decoration: BoxDecoration(
                                color: theme.colorScheme.surfaceContainerHighest
                                    .withOpacity(0.3),
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(
                                  color: theme.colorScheme.outline.withOpacity(
                                    0.3,
                                  ),
                                ),
                              ),
                              child: DropdownButtonFormField<dynamic>(
                                value: _selectedCategory,
                                items: _categories
                                    .map(
                                      (c) => DropdownMenuItem<dynamic>(
                                        value: c,
                                        child: Text(
                                          c['name']?.toString() ?? 'Category',
                                        ),
                                      ),
                                    )
                                    .toList(),
                                onChanged: (val) {
                                  setState(() {
                                    _selectedCategory = val;
                                  });
                                },
                                decoration: const InputDecoration(
                                  border: InputBorder.none,
                                  contentPadding: EdgeInsets.symmetric(
                                    horizontal: 16,
                                    vertical: 12,
                                  ),
                                ),
                                icon: Icon(
                                  Icons.keyboard_arrow_down,
                                  color: theme.colorScheme.secondary,
                                ),
                              ),
                            ),
                    ),
                    _ModernFormField(
                      label: 'Receipt',
                      child: InkWell(
                        onTap: _saving
                            ? null
                            : () async {
                                final picked = await _imagePicker.pickImage(
                                  source: ImageSource.gallery,
                                );
                                if (picked != null) {
                                  setState(() {
                                    _receiptImage = picked;
                                  });
                                }
                              },
                        borderRadius: BorderRadius.circular(12),
                        child: Container(
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: theme.colorScheme.surfaceContainerHighest
                                .withOpacity(0.3),
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(
                              color: theme.colorScheme.outline.withOpacity(0.3),
                            ),
                          ),
                          child: Row(
                            children: [
                              Icon(
                                _receiptImage != null
                                    ? Icons.receipt_long
                                    : Icons.add_photo_alternate,
                                color: theme.colorScheme.primary,
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Text(
                                  _receiptImage != null
                                      ? _receiptImage!.name
                                      : 'Choose image (bill/receipt)',
                                  style: theme.textTheme.bodyMedium,
                                ),
                              ),
                              if (_receiptImage != null)
                                IconButton(
                                  onPressed: () {
                                    setState(() {
                                      _receiptImage = null;
                                    });
                                  },
                                  icon: Icon(
                                    Icons.clear,
                                    color: theme.colorScheme.error,
                                  ),
                                ),
                            ],
                          ),
                        ),
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: theme.colorScheme.primaryContainer.withOpacity(
                          0.3,
                        ),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(
                          color: theme.colorScheme.primary.withOpacity(0.3),
                        ),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Icon(
                                Icons.group,
                                color: theme.colorScheme.primary,
                              ),
                              const SizedBox(width: 8),
                              Text(
                                'Split Details',
                                style: theme.textTheme.titleSmall?.copyWith(
                                  fontWeight: FontWeight.w600,
                                  color: theme.colorScheme.primary,
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 8),
                          Text(
                            _splitType == 'equal'
                                ? 'This will be split equally among all group members:'
                                : _splitType == 'custom'
                                ? 'Enter amount for each member (must sum to total):'
                                : 'Enter percentage for each member (must sum to 100%):',
                            style: theme.textTheme.bodyMedium,
                          ),
                          const SizedBox(height: 12),
                          ..._members.asMap().entries.map((entry) {
                            final index = entry.key;
                            final m = entry.value;
                            final user = m['user'] as Map<String, dynamic>?;
                            final name = user?['name']?.toString() ?? 'Member';
                            final email = user?['email']?.toString() ?? '';

                            Widget? trailing;
                            if (_splitType == 'custom') {
                              trailing = SizedBox(
                                width: 100,
                                child: TextField(
                                  controller: _amountCtrls[index],
                                  keyboardType:
                                      const TextInputType.numberWithOptions(
                                        decimal: true,
                                      ),
                                  style: theme.textTheme.bodyMedium,
                                  decoration: InputDecoration(
                                    hintText: 'Amount',
                                    filled: true,
                                    fillColor: theme.colorScheme.surface,
                                    border: OutlineInputBorder(
                                      borderRadius: BorderRadius.circular(8),
                                    ),
                                    contentPadding: const EdgeInsets.symmetric(
                                      horizontal: 12,
                                      vertical: 8,
                                    ),
                                  ),
                                ),
                              );
                            } else if (_splitType == 'percentage') {
                              trailing = SizedBox(
                                width: 100,
                                child: TextField(
                                  controller: _percentCtrls[index],
                                  keyboardType:
                                      const TextInputType.numberWithOptions(
                                        decimal: true,
                                      ),
                                  style: theme.textTheme.bodyMedium,
                                  decoration: InputDecoration(
                                    hintText: '%',
                                    filled: true,
                                    fillColor: theme.colorScheme.surface,
                                    border: OutlineInputBorder(
                                      borderRadius: BorderRadius.circular(8),
                                    ),
                                    contentPadding: const EdgeInsets.symmetric(
                                      horizontal: 12,
                                      vertical: 8,
                                    ),
                                  ),
                                ),
                              );
                            }

                            final imageUrl = user?['avatar']?.toString();
                            final hasValidImage =
                                imageUrl != null && imageUrl.startsWith('http');
                            return Container(
                              margin: const EdgeInsets.only(bottom: 8),
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(
                                color: theme.colorScheme.surface,
                                borderRadius: BorderRadius.circular(8),
                                border: Border.all(
                                  color: theme.colorScheme.outline.withOpacity(
                                    0.3,
                                  ),
                                ),
                              ),
                              child: Row(
                                children: [
                                  CircleAvatar(
                                    radius: 16,
                                    backgroundColor:
                                        theme.colorScheme.secondaryContainer,
                                    backgroundImage: hasValidImage
                                        ? NetworkImage(imageUrl!)
                                        : null,
                                    child: Icon(
                                      Icons.person,
                                      size: 16,
                                      color: theme.colorScheme.secondary,
                                    ),
                                  ),
                                  const SizedBox(width: 12),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          name,
                                          style: theme.textTheme.bodyMedium
                                              ?.copyWith(
                                                fontWeight: FontWeight.w500,
                                              ),
                                        ),
                                        if (email.isNotEmpty)
                                          Text(
                                            email,
                                            style: theme.textTheme.bodySmall
                                                ?.copyWith(
                                                  color: theme
                                                      .colorScheme
                                                      .onSurfaceVariant,
                                                ),
                                          ),
                                      ],
                                    ),
                                  ),
                                  if (trailing != null) trailing,
                                ],
                              ),
                            );
                          }),
                        ],
                      ),
                    ),
                    const SizedBox(height: 24),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        onPressed: _saving ? null : _save,
                        style: ElevatedButton.styleFrom(
                          padding: const EdgeInsets.symmetric(vertical: 16),
                          backgroundColor: theme.colorScheme.secondary,
                          foregroundColor: theme.colorScheme.onSecondary,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                          elevation: 2,
                        ),
                        child: _saving
                            ? const SizedBox(
                                width: 24,
                                height: 24,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                  valueColor: AlwaysStoppedAnimation<Color>(
                                    Colors.white,
                                  ),
                                ),
                              )
                            : Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  const Icon(Icons.call_split),
                                  const SizedBox(width: 8),
                                  const Text('Split expense'),
                                ],
                              ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
    );
  }
}

class _ModernFormField extends StatelessWidget {
  const _ModernFormField({
    required this.label,
    required this.child,
    this.required = false,
  });

  final String label;
  final Widget child;
  final bool required;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(bottom: 8),
          child: RichText(
            text: TextSpan(
              text: label,
              style: theme.textTheme.bodyLarge?.copyWith(
                fontWeight: FontWeight.w600,
                color: theme.colorScheme.onSurface,
              ),
              children: required
                  ? [
                      TextSpan(
                        text: ' *',
                        style: TextStyle(
                          color: theme.colorScheme.error,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ]
                  : null,
            ),
          ),
        ),
        child,
        const SizedBox(height: 16),
      ],
    );
  }
}
