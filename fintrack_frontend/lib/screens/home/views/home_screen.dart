import 'dart:math';
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
  final Color selectedItem = Colors.blue;
  final Color unselectedItem = Colors.grey;
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
    return Scaffold(
      backgroundColor: Colors.grey[100],

      // Bottom navigation bar
      bottomNavigationBar: ClipRRect(
        borderRadius: const BorderRadius.vertical(top: Radius.circular(30)),
        child: BottomNavigationBar(
          currentIndex: index,
          onTap: (value) => setState(() => index = value),
          showSelectedLabels: false,
          showUnselectedLabels: false,
          items: [
            BottomNavigationBarItem(
              icon: Icon(
                CupertinoIcons.home,
                color: index == 0 ? selectedItem : unselectedItem,
              ),
              label: "Home",
            ),
            BottomNavigationBarItem(
              icon: Icon(
                CupertinoIcons.graph_square_fill,
                color: index == 1 ? selectedItem : unselectedItem,
              ),
              label: "Stats",
            ),
          ],
        ),
      ),

      // Floating action button (optional, you can remove if not needed)
      floatingActionButtonLocation: FloatingActionButtonLocation.centerDocked,
      floatingActionButton: FloatingActionButton(
        shape: const CircleBorder(),
        onPressed: _openAddTransaction,
        child: Container(
          width: 60,
          height: 60,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            gradient: LinearGradient(
              colors: [
                Theme.of(context).colorScheme.tertiary ??
                    Theme.of(context).colorScheme.primary,
                Theme.of(context).colorScheme.secondary,
                Theme.of(context).colorScheme.primary,
              ],
              transform: const GradientRotation(pi / 4),
            ),
          ),
          child: const Icon(CupertinoIcons.add),
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
              onAddTransaction: _openAddTransaction,
            )
          : const StatScreen(),
    );
  }
}
