import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:schooledge_mobile/app/app.dart';

void main() {
  testWidgets('shows the SchoolEdge shell', (tester) async {
    await tester.pumpWidget(const ProviderScope(child: SchoolEdgeApp()));
    await tester.pump();
    expect(find.text('SchoolEdge'), findsOneWidget);
  });
}
