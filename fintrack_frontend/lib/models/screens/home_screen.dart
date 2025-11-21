import 'package:flutter/material.dart';
import 'package:supabase_flutter/supabase_flutter.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  final supabase = Supabase.instance.client;

  Stream<List<Map<String, dynamic>>> groupStream() async* {
    final userId = supabase.auth.currentUser!.id;

    while (true) {
      final ownedGroups = await supabase
          .from('groups')
          .select()
          .eq('owner_id', userId);

      final memberGroups = await supabase
          .from('group_members')
          .select('groups(*)')
          .eq('user_id', userId);

      final combined = [
        ...ownedGroups,
        ...memberGroups.map((m) => m['groups']).where((g) => g != null),
      ];

      yield List<Map<String, dynamic>>.from(combined);

      await Future.delayed(const Duration(seconds: 2));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF1F5FF),
      appBar: AppBar(
        backgroundColor: Colors.blue,
        elevation: 0,
        title: const Text(
          "Your Groups",
          style: TextStyle(color: Colors.white),
        ),
        centerTitle: true,
      ),

      floatingActionButton: FloatingActionButton(
        backgroundColor: Colors.blue,
        onPressed: () {},
        child: const Icon(Icons.add, color: Colors.white),
      ),

      body: StreamBuilder<List<Map<String, dynamic>>>(
        stream: groupStream(),
        builder: (context, snapshot) {
          if (!snapshot.hasData) {
            return const Center(
                child: CircularProgressIndicator(color: Colors.blue));
          }

          final groups = snapshot.data!;

          if (groups.isEmpty) {
            return const Center(
              child: Text(
                "No groups found\nCreate one to get started!",
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 18),
              ),
            );
          }

          return ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: groups.length,
            itemBuilder: (context, i) {
              final g = groups[i];

              return Container(
                margin: const EdgeInsets.only(bottom: 14),
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(14),
                  boxShadow: const [
                    BoxShadow(
                      blurRadius: 10,
                      color: Colors.black12,
                      offset: Offset(0, 4),
                    )
                  ],
                ),

                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      g['name'] ?? "Unnamed Group",
                      style: const TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                      ),
                    ),

                    const SizedBox(height: 6),

                    Text(
                      g['description'] ?? "No description",
                      style: const TextStyle(fontSize: 14, color: Colors.grey),
                    ),

                    const SizedBox(height: 12),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Row(
                          children: const [
                            Icon(Icons.group, color: Colors.blue),
                            SizedBox(width: 6),
                            Text("Members"),
                          ],
                        ),
                        const Icon(Icons.arrow_forward_ios, size: 18)
                      ],
                    )
                  ],
                ),
              );
            },
          );
        },
      ),
    );
  }
}
