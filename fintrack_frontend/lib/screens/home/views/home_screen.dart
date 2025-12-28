import 'package:flutter/material.dart';
import 'package:flutter/cupertino.dart';

import 'main_screen.dart';
import '../../stats/stats.dart';
import '../../../services/api_service.dart';
import '../../add_expense/views/add_transaction_screen.dart';

class HomeScreen extends StatefulWidget {
  final Map<String, dynamic> user;
  final Map<String, dynamic> dashboard;

  const HomeScreen({super.key, required this.user, required this.dashboard});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  int index = 0;
  final Color selectedItem = const Color(0xFF1565C0); // Darker blue
  final Color unselectedItem = const Color(0xFF616161); // Grey 700
  late Map<String, dynamic> _dashboard;

  @override
  void initState() {
    super.initState();
    _dashboard = widget.dashboard;
  }

  Future<void> _openAddTransaction() async {
    final created = await Navigator.push<bool>(
      context,
      MaterialPageRoute(builder: (_) => const AddTransactionScreen()),
    );

    if (created == true) {
      try {
        final refreshed = await ApiService.fetchDashboard();
        if (!mounted) return;
        setState(() {
          _dashboard = refreshed;
        });
      } catch (_) {
        // You might want to show a snackbar in a later refinement.
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      backgroundColor: Colors.grey[100],

      // Bottom navigation bar
      bottomNavigationBar: Padding(
        padding: const EdgeInsets.fromLTRB(4, 0, 4, 4),
        child: PhysicalModel(
          color: Colors.transparent,
          elevation: 24,
          borderRadius: BorderRadius.circular(32),
          clipBehavior: Clip.antiAlias,
          child: BottomAppBar(
            color: theme.colorScheme.surfaceContainerHigh,
            shape: const CircularNotchedRectangle(),
            notchMargin: 8,
            child: SizedBox(
              height: 64,
              child: Row(
                children: [
                  const SizedBox(width: 16),
                  _NavItem(
                    icon: CupertinoIcons.home,
                    label: 'Home',
                    selected: index == 0,
                    onTap: () => setState(() => index = 0),
                    selectedColor: selectedItem,
                    unselectedColor: unselectedItem,
                  ),
                  const Spacer(), // center gap under FAB notch
                  _NavItem(
                    icon: CupertinoIcons.doc_chart,
                    label: 'Reports',
                    selected: index == 1,
                    onTap: () => setState(() => index = 1),
                    selectedColor: selectedItem,
                    unselectedColor: unselectedItem,
                  ),
                  const SizedBox(width: 16),
                ],
              ),
            ),
          ),
        ),
      ),

      // Floating action button (optional, you can remove if not needed)
      floatingActionButtonLocation: FloatingActionButtonLocation.centerDocked,
      floatingActionButton: FloatingActionButton(
        shape: const CircleBorder(),
        onPressed: _openAddTransaction,
        child: Container(
          width: 100,
          height: 100,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
<<<<<<< HEAD
            color: theme.colorScheme.primary,
=======
            gradient: LinearGradient(
              colors: [
                Theme.of(context).colorScheme.tertiary,
                Theme.of(context).colorScheme.secondary,
                Theme.of(context).colorScheme.primary,
              ],
              transform: const GradientRotation(pi / 4),
            ),
>>>>>>> 80b062ea2566594326e2594a6513824d6ece807c
          ),
          child: const Icon(CupertinoIcons.add, color: Colors.white),
        ),
      ),

      // Body
      body: index == 0
          ? MainScreen(
              userName: widget.user['name'],
              statistics: _dashboard['statistics'],
              budgets: _dashboard['budgets'],
              insights: _dashboard['insights'],
              transactions: _dashboard['transactions'],
              rawDashboard: _dashboard['raw'],
              onAddTransaction: _openAddTransaction,
            )
          : StatScreen(rawDashboard: _dashboard['raw']),
    );
  }
}

class _NavItem extends StatelessWidget {
  final IconData icon;
  final String label;
  final bool selected;
  final VoidCallback onTap;
  final Color selectedColor;
  final Color unselectedColor;

  const _NavItem({
    required this.icon,
    required this.label,
    required this.selected,
    required this.onTap,
    required this.selectedColor,
    required this.unselectedColor,
  });

  @override
  Widget build(BuildContext context) {
    final color = selected ? selectedColor : unselectedColor;

    return InkWell(
      borderRadius: BorderRadius.circular(24),
      onTap: onTap,
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, color: color),
            const SizedBox(height: 4),
            Text(
              label,
              style: TextStyle(
                color: color,
                fontSize: 11,
                fontWeight: selected ? FontWeight.w600 : FontWeight.w400,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
