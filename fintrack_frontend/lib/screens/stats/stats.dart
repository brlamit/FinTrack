import 'package:flutter/material.dart';

class StatScreen extends StatelessWidget {
  const StatScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Stats')),
      body: const Center(child: Text('Stats screen (stub)')),
    );
  }
}
