using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Data;
using System.Windows.Documents;
using System.Windows.Input;
using System.Windows.Media;
using System.Windows.Media.Imaging;
using System.Windows.Shapes;

namespace BootsApp
{
    /// <summary>
    /// Логика взаимодействия для UserMainWindow.xaml
    /// </summary>
    public partial class UserMainWindow : Window
    {
        public bootsEntities2 _context;
        public UserMainWindow()
        {
            InitializeComponent();
            _context = bootsEntities2.GetContext();
            DgTovar.ItemsSource = _context.Tovars.ToList();
        }

        private void Button_Click(object sender, RoutedEventArgs e)
        {
            UserPayWindow uw = new UserPayWindow();
            uw.Show();

        }

        private void Button_Click_1(object sender, RoutedEventArgs e)
        {
            MainWindow mw = new MainWindow();
            mw.Show();
            this.Close();
        }
    }
}
